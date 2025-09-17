<?php

namespace Modules\TelegramChannel\Services;

use Modules\TelegramChannel\Actions\ExtractTextFromMessage;
use Modules\TelegramChannel\Actions\ChannelRuleMatcher;
use Modules\TelegramChannel\Entities\TelegramVacancy;
use Modules\TelegramChannel\Services\Telegram\MadelineClient;
use App\Models\TelegramChannel; // Sizning Controller shuni ishlatyapti
use Illuminate\Support\Facades\Log;

class RelayService
{
    public function __construct(
        private MadelineClient $tg,
        private ExtractTextFromMessage $extract,
        private ChannelRuleMatcher $matcher,
    ) {}

    public function syncOneByUsername(string $peer): void
    {
        // DB da shu kanal bor deb faraz qilamiz (username yoki channel_id orqali)
        if (preg_match('/^-?\d+$/', (string) $peer)) {
            $channel = TelegramChannel::where('channel_id', (string) $peer)->first();
            $ruleKey = (string) $peer; // Rul topilmasa default allow
        } else {
            $channel = TelegramChannel::where('username', ltrim((string) $peer, '@'))->first();
            $ruleKey = (string) $peer; // matcher ichida '@' bilan/bo'lmasdan tekshiriladi
        }
        $target  = TelegramChannel::where('is_target', true)->first();

        // Agar yo'q bo'lsa — hech bo'lmasa "stateless" qilib o'qish
        $lastId  = (int) ($channel?->last_message_id ?? 0);

        // Birinchi ishga tushirishda eski postlarni OLMASLIK: hozirgi eng so'nggi id ni anchor qilib qo'yamiz
        if ($lastId <= 0) {
            $latest = $this->tg->getHistory($peer, 0, 1);
            $messages = $latest['messages'] ?? [];
            $latestId = 0;
            foreach ($messages as $m) {
                $id = (int) ($m['id'] ?? 0);
                if ($id > $latestId) $latestId = $id;
            }
            if ($channel && $latestId > 0) {
                $channel->last_message_id = $latestId;
                $channel->save();
            }
            // Eski postlarni relay qilmaymiz
            return;
        }

        $limit = (int) config('telegramchannel_relay.fetch.batch_limit', 100);
        $sleep = (int) config('telegramchannel_relay.fetch.sleep_sec', 2);

        while (true) {
            $hist = $this->tg->getHistory($peer, $lastId, $limit);
            $messages = $hist['messages'] ?? [];
            if (empty($messages)) break;

            $maxId = $lastId;
            foreach ($messages as $m) {
                $id = (int) ($m['id'] ?? 0);
                if ($id <= 0) continue;
                if ($id > $maxId) $maxId = $id;

                $text = $this->extract->handle($m);
                if ($text === null) continue;

                // Kanalga xos qoida bo'yicha filtr
                if (!$this->matcher->matches($ruleKey, $text)) {
                    continue;
                }

                // Dublikat nazorati: agar shu matn 'publish' statusida mavjud bo'lsa — SKIP
                $hasPublished = TelegramVacancy::where('description', $text)
                    ->where('status', 'publish')
                    ->exists();

                if ($hasPublished) {
                    continue;
                }

                // Yangi yozuvni default 'publish' statusida saqlaymiz
                TelegramVacancy::create([
                    'description' => $text,
                    'status' => 'publish',
                ]);

                // Agar target kanal bor bo'lsa, matnni yuboramiz
                if ($target) {
                    $to = $target->channel_id ?: ($target->username ?? null);
                    if ($to) {
                        try {
                            // Default: textni nusxalab yuborish (forward emas)
                            $this->tg->sendMessage($to, $text);
                        } catch (\Throwable $e) {
                            // Targetga yuborishda xatolik bo'lsa, tsiklni to'xtatmaymiz, lekin logga yozamiz
                            Log::warning('Telegram relay: sendMessage failed', [
                                'error' => $e->getMessage(),
                                'to' => $to,
                                'source' => $peer,
                                'message_id' => $id,
                            ]);
                        }
                    }
                }
            }

            // Oxirgi ko'rilgan id ni saqlaymiz (agar kanal DB da bo'lsa)
            if ($channel && $maxId > $lastId) {
                $channel->last_message_id = $maxId;
                $channel->save();
            }

            $lastId = $maxId;
            sleep($sleep);
        }
    }
}
