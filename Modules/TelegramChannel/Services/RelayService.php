<?php

namespace Modules\TelegramChannel\Services;

use Modules\TelegramChannel\Actions\ExtractTextFromMessage;
use Modules\TelegramChannel\Actions\ChannelRuleMatcher;
use Modules\TelegramChannel\Actions\TransformMessageText;
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
        private TransformMessageText $transform,
        private VacancyClassificationService $classifier,
        private VacancyNormalizationService $normalizer,
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

        $maxLoops = (int) config('telegramchannel_relay.fetch.max_loops_per_run', 1);
        $loops = 0;
        while (true) {
            $hist = $this->tg->getHistory($peer, $lastId, $limit);
            $messages = $hist['messages'] ?? [];
            if (empty($messages)) break;

            // gpt apiga sorov jonatiladi 
            $maxId = $lastId;
            foreach ($messages as $m) {
                $id = (int) ($m['id'] ?? 0);
                if ($id <= 0) continue;
                if ($id > $maxId) $maxId = $id;

                $text = $this->extract->handle($m);
                if ($text === null) continue;

                // Pre-filter (optional) by channel rules
                if ((bool) config('telegramchannel_relay.filtering.use_channel_rules', true)) {
                    if (!$this->matcher->matches($ruleKey, $text)) {
                        continue;
                    }
                }

                // Quick skip by banned phrases (job seeker banners like "Ish joyi kerak", etc.)
                $banned = (array) config('telegramchannel_relay.filtering.banned_phrases', []);
                if (!empty($banned)) {
                    $lower = mb_strtolower($text);
                    foreach ($banned as $phrase) {
                        $p = mb_strtolower((string) $phrase);
                        if ($p !== '' && preg_match('/^\s*' . preg_quote($p, '/') . '\b/u', $lower)) {
                            continue 2; // skip this message
                        }
                    }
                }

                // Require username for source link; skip numeric-only without username
                $plainSource = null;
                if (preg_match('/^-?\d+$/', (string) $peer)) {
                    $plainSource = $channel?->username ? ltrim((string) $channel->username, '@') : null;
                } else {
                    $plainSource = ltrim((string) $peer, '@');
                }
                if (!$plainSource) {
                    // Username is required to build a clickable source link
                    Log::warning('Skipping message without source username', ['peer' => $peer, 'message_id' => $id]);
                    continue;
                }

                // Build source link and quick dedupe by (source_id, source_message_id)
                $sourceId   = '@' . $plainSource;
                $sourceLink = 'https://t.me/' . $plainSource . '/' . $id;
                $existsByLink = \Modules\TelegramChannel\Entities\TelegramVacancy::where('source_id', $sourceId)
                    ->where('source_message_id', $sourceLink)
                    ->exists();
                if ($existsByLink) {
                    continue; // strict no-duplicate policy
                }

                // Classify content to ensure it's an employer vacancy
                try {
                    $cls = $this->classifier->classify($text);
                } catch (\Throwable $e) {
                    Log::error('Classification error', ['err' => $e->getMessage()]);
                    continue; // skip on error; keep loop running
                }
                $threshold = (float) config('telegramchannel_relay.filtering.classification_threshold', 0.8);
                if (($cls['label'] ?? '') !== 'employer_vacancy' || (float) ($cls['confidence'] ?? 0) < $threshold) {
                    continue;
                }

                // Normalize via OpenAI
                try {
                    $normalized = $this->normalizer->normalize($text, '@'.$plainSource, $id);
                } catch (\Throwable $e) {
                    Log::error('Normalization error', ['err' => $e->getMessage()]);
                    continue; // skip on error
                }

                // Post-normalization guard: if title is blacklisted (generic slogans) — skip
                $sanTitle = trim((string) ($normalized['title'] ?? ''));
                $sanTitle = trim($sanTitle, " :\t\r\n");
                $titleLower = mb_strtolower($sanTitle);
                $titleBlacklist = (array) config('telegramchannel_relay.filtering.title_blacklist', []);
                foreach ($titleBlacklist as $tb) {
                    if ($titleLower === mb_strtolower((string) $tb)) {
                        continue 2;
                    }
                }

                // Require contact: at least one phone or telegram username must be present
                $requireContact = (bool) config('telegramchannel_relay.filtering.require_contact', false);
                $phones = (array) ($normalized['contact']['phones'] ?? []);
                $users  = (array) ($normalized['contact']['telegram_usernames'] ?? []);
                if ($requireContact && empty($phones) && empty($users)) {
                    continue; // skip if no contacts provided
                }

                // Compute signature and cross-channel dedupe
                $signature = \Modules\TelegramChannel\Support\Signature::fromNormalized($normalized);
                if ($signature !== '') {
                    $existsBySig = \Modules\TelegramChannel\Entities\TelegramVacancy::where('signature', $signature)->exists();
                    if ($existsBySig) {
                        continue;
                    }
                }

                // Render post in your house style (Blade)
                // $phones and $users already prepared above
                $targetUsername = $target?->username ? '@'.ltrim((string) $target->username, '@') : null;
                $html = view('telegramchannel::templates.vacancy_post', [
                    'title' => $normalized['title'] ?? '',
                    'company' => $normalized['company'] ?? '',
                    'phones' => $phones,
                    'usernames' => $users,
                    'description' => $normalized['description'] ?? '',
                    'source_link' => $sourceLink,
                    'plain_username' => $plainSource,
                    'target_username' => $targetUsername,
                ])->render();

                // Optional final transform layer (regex replacements)
                $out = $this->transform->handle($ruleKey, $html, $target);

                // Send to target
                $targetLink = null;
                if ($target) {
                    // Prefer @username for peer; fallback to numeric channel_id
                    $to = null;
                    if (!empty($target->username)) {
                        $to = '@' . ltrim((string) $target->username, '@');
                    } elseif (!empty($target->channel_id)) {
                        $to = (string) $target->channel_id;
                    }
                    if ($to) {
                        try {
                            $resp = $this->tg->sendMessage($to, $out);
                            // Try to extract message id from response for target link
                            $tUser = $target->username ? ltrim((string) $target->username, '@') : null;
                            $tMsgId = $resp['id'] ?? ($resp['updates'][0]['message']['id'] ?? null);
                            if ($tUser && $tMsgId) {
                                $targetLink = 'https://t.me/' . $tUser . '/' . $tMsgId;
                            }
                        } catch (\Throwable $e) {
                            Log::warning('Telegram relay: sendMessage failed', [
                                'error' => $e->getMessage(),
                                'to' => $to,
                                'source' => $peer,
                                'message_id' => $id,
                            ]);
                            continue; // skip saving if sending failed
                        }
                    }
                }

                // Save to telegram_vacancies (strict dedupe already passed)
                try {
                    \Modules\TelegramChannel\Entities\TelegramVacancy::create([
                        'title' => $normalized['title'] ?? null,
                        'company' => $normalized['company'] ?? null,
                        'contact' => [
                            'phones' => $phones,
                            'telegram_usernames' => $users,
                        ],
                        'description' => $normalized['description'] ?? null,
                        'language' => $normalized['language'] ?? null,
                        'status' => 'publish',
                        'source_id' => $sourceId,
                        'source_message_id' => $sourceLink,
                        'target_message_id' => $targetLink,
                        'signature' => $signature,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Failed to save TelegramVacancy', ['err' => $e->getMessage()]);
                }
            }

            // Oxirgi ko'rilgan id ni saqlaymiz (agar kanal DB da bo'lsa)
            if ($channel && $maxId > $lastId) {
                $channel->last_message_id = $maxId;
                $channel->save();
            }

            $lastId = $maxId;
            $loops++;
            if ($loops >= $maxLoops) {
                break;
            }
            sleep($sleep);
        }
    }
}
