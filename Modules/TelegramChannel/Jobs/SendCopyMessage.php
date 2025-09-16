<?php

namespace Modules\TelegramChannel\Jobs;

use App\Models\TelegramChannel;
use App\Models\Vacancy;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Logger;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
// No file logging
use Illuminate\Contracts\Cache\LockTimeoutException;

class SendCopyMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries = 5;
    public $backoff = [5, 10, 20, 40, 80];

    public function __construct(public int $channelDbId, public int $messageId, public ?string $text = null, public ?array $raw = null)
    {
    }

    public function handle(): void
    {
        $source = TelegramChannel::find($this->channelDbId);
        $target = TelegramChannel::where('is_target', true)->first();
        if (!$source || !$target) return;

        // no log

        // Idempotent: shu xabar avval saqlanganmi?
        $external = 'telegram:'.($source->channel_id ?? 'unknown').':'.$this->messageId;
        $redis = app('redis')->connection();
        $sentKey = 'tg:sent:'.$external;
        if ($redis->exists($sentKey)) { return; }
        if (Vacancy::where('external_id', $external)->exists()) {
            $ttl = (int) config('telegramchannel.vacancy_ttl_seconds', 604800);
            $redis->setex($sentKey, max(60, $ttl), '1');
            return;
        }

        $api = null;
        $sourcePeer = null;
        $targetPeer = null;
        // Global lock: MadelineProto sessiya bilan to'qnashuvni oldini olish
        $lock = Cache::lock('tg:api:lock', 30);
        try {
            $lock->block(60);
        } catch (LockTimeoutException $e) {
            // API band: birozdan keyin yana urinib ko'ramiz
            $this->release(5);
            return;
        }
        try {
            $api = $this->makeApi();
            $api->start();

            $sourcePeer = $this->resolvePeer($api, $source);
            $targetPeer = $this->resolvePeer($api, $target);
            if (!$sourcePeer || !$targetPeer) { return; }

            $msg = $this->raw ?? $this->fetchMessage($api, $sourcePeer, $this->messageId);
            if (!$msg) { return; }

        $textOnly = (bool) config('telegramchannel.text_only', true);
        $origText = $this->text ?? (string) ($msg['message'] ?? '');
        $hasText = strlen(trim($origText)) > 0;
        if ($textOnly && !$hasText) { return; }

        $replacementHandle = $target->username ? '@'.ltrim((string) $target->username, '@') : null;
        $sourceHandle = strtolower(ltrim((string) ($source->username ?? $source->channel_id ?? ''), '@'));
        $strictSources = ['ustozshogird', 'ustozshogirdsohalar'];
        $strict = in_array($sourceHandle, $strictSources, true);
        [$vacTitle, $descText, $shouldSkip] = $this->processJobPost($origText, $strict, $replacementHandle);
        if ($shouldSkip) { return; }

        if ($descText !== '') {
            $publishExists = Vacancy::where('description', $descText)
                ->where('status', \App\Models\Vacancy::STATUS_PUBLISH)
                ->exists();
            if ($publishExists) { return; }
        }

        if (!$this->acquireRateSlots((int) $this->getChatId($target))) { $this->release(1); return; }

        $outText = $vacTitle ? ($vacTitle.":\n".$descText) : $descText;
        if (trim((string) $outText) === '') { return; }

            $result = $api->messages->sendMessage([
                'peer' => $targetPeer,
                'message' => $outText,
            ]);

            $newId = $this->extractMessageId($result);
            if (!$newId) {
                $latest = $api->messages->getHistory([
                    'peer' => $targetPeer,
                    'offset_id' => 0,
                    'offset_date' => 0,
                    'add_offset' => 0,
                    'limit' => 1,
                    'max_id' => 0,
                    'min_id' => 0,
                    'hash' => 0,
                ]);
                $newId = (int) (($latest['messages'][0]['id'] ?? 0));
            }
        } catch (\Throwable $e) {
            $sec = $this->parseFloodWait($e->getMessage());
            if ($sec > 0) {
                Log::warning('FloodWait detected, delaying '.$sec.'s');
                $this->release($sec + 1);
                return;
            }
            throw $e;
        } finally {
            // Lockni tezroq bo'shatamiz (saqlash DB da davom etadi)
            optional($lock)->release();
        }

        try {
            $applyUrl = $this->buildMessageUrl($target, $newId);
            $ttl = (int) config('telegramchannel.vacancy_ttl_seconds', 604800);
            // Mark as sent to prevent duplicates even if DB save fails
            $redis->setex($sentKey, max(60, $ttl), (string) $newId);
            $expDate = null;
            if ($ttl >= 86400) {
                $days = (int) ceil($ttl / 86400);
                $expDate = now()->addDays($days)->toDateString();
            }

            $attrs = [
                'source' => 'Telegram',
                'title' => $vacTitle ?: null,
                'description' => $descText,
                'apply_url' => $applyUrl,
                'status' => \App\Models\Vacancy::STATUS_PUBLISH,
                'raw_data' => json_encode($msg, JSON_UNESCAPED_UNICODE),
            ];
            $vac = Vacancy::updateOrCreate(
                ['external_id' => $external],
                $attrs
            );
            // no log
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    private function makeApi(): API
    {
        $settings = new Settings;
        $settings->getAppInfo()->setApiId((int) config('telegramchannel.api_id'));
        $settings->getAppInfo()->setApiHash((string) config('telegramchannel.api_hash'));
        // Lower MadelineProto verbosity
        $settings->getLogger()->setLevel(Logger::LEVEL_ERROR);
        return new API((string) config('telegramchannel.session'), $settings);
    }

    private function resolvePeer(API $api, TelegramChannel $channel)
    {
        $id = trim((string) ($channel->channel_id ?? ''));
        $username = trim((string) ($channel->username ?? ''));

        $candidates = [];

        // Username-based candidates (avoid duplicating http-prefixed)
        if ($username !== '' && !Str::startsWith($username, 'http')) {
            $candidates[] = $username;
            if ($username[0] !== '@') {
                $candidates[] = '@'.$username;
            }
            $candidates[] = 'https://t.me/'.ltrim($username, '@');
        }

        // ID or link-based candidates
        if ($id !== '') {
            if (Str::startsWith($id, 'http')) {
                // Join link support
                if (preg_match('~t\.me/\+([A-Za-z0-9_-]+)$~', $id, $m)) {
                    try { $api->messages->importChatInvite(['hash' => $m[1]]); } catch (\Throwable $e) {}
                }
                // Private channel /c/<id>/
                if (preg_match('~t\.me/c/(\d+)/(?:\d+)~', $id, $m)) {
                    $candidates[] = '-100'.$m[1];
                }
                $candidates[] = $id;
            } else {
                $candidates[] = $id;
                // If it's not a -100... numeric ID, also try @ and https forms
                if (!Str::startsWith($id, '-100') && !ctype_digit(str_replace('-', '', $id))) {
                    $candidates[] = '@'.ltrim($id, '@');
                    $candidates[] = 'https://t.me/'.ltrim($id, '@');
                }
            }
        }

        $candidates = array_values(array_unique($candidates));
        foreach ($candidates as $cand) {
            try {
                return $api->resolvePeer($cand);
            } catch (\Throwable $e1) {
                // Fallback to getInfo to obtain bot_api_id
                try {
                    $info = $api->getInfo($cand);
                    if (is_array($info) && isset($info['bot_api_id'])) {
                        return $info['bot_api_id'];
                    }
                } catch (\Throwable $e2) {
                    // try next candidate
                }
            }
        }

        // Last resort: direct cast for -100... numeric chat IDs
        if ($id !== '' && Str::startsWith($id, '-100') && ctype_digit(ltrim($id, '-'))) {
            return (int) $id;
        }

        return null;
    }

    private function fetchMessage(API $api, $peer, int $messageId): ?array
    {
        try {
            $hist = $api->messages->getHistory([
                'peer' => $peer,
                'offset_id' => 0,
                'offset_date' => 0,
                'add_offset' => 0,
                'limit' => 5,
                'max_id' => 0,
                'min_id' => $messageId - 1,
                'hash' => 0,
            ]);
            foreach (($hist['messages'] ?? []) as $m) {
                if ((int) ($m['id'] ?? 0) === $messageId) return $m;
            }
        } catch (\Throwable) {}
        return null;
    }

    private function extractMessageId($result): int
    {
        $id = 0;
        $walk = function ($n) use (&$walk, &$id) {
            if ($id) return;
            if (is_array($n)) {
                if (isset($n['message']['id'])) { $id = (int) $n['message']['id']; return; }
                if (isset($n['id']) && is_int($n['id'])) { $id = (int) $n['id']; return; }
                foreach ($n as $v) $walk($v);
            }
        };
        $walk($result);
        return $id;
    }

    /**
     * Process job post text: extract title (e.g., "Xodim kerak"),
     * replace/remove channel signatures, and decide skip based on strict channels.
     *
     * @return array{0:?string,1:string,2:bool} [$title, $description, $shouldSkip]
     */
    private function processJobPost(string $text, bool $strict, ?string $replacementHandle = null): array
    {
        $lines = preg_split('/\R/', $text);
        $clean = [];
        foreach ($lines as $line) {
            $ln = rtrim((string) $line);
            if ($strict) {
                // Replace signature lines like: "👉 @UstozShogird kanaliga ulanish" with our own handle
                if (preg_match('/@UstozShogird/i', $ln)) {
                    if ($replacementHandle) {
                        $ln = '👉 '.$replacementHandle.' kanaliga ulanish';
                    } else {
                        continue;
                    }
                } else if ($replacementHandle) {
                    // Also replace inline occurrences within the line
                    $ln = preg_replace('/@UstozShogird/i', $replacementHandle, $ln);
                }
            }
            $clean[] = $ln;
        }

        // Remove leading/trailing blank lines
        $clean = $this->trimEmptyLines($clean);

        // If strict source: must contain "Xodim kerak"; else accept any text
        $containsTitle = (bool) preg_match('/Xodim\s+kerak/iu', $text);
        $title = $containsTitle ? 'Xodim kerak' : null;

        // If the first non-empty line itself is a title, drop it from description
        if (!empty($clean)) {
            $first = ltrim((string) $clean[0]);
            if (preg_match('/^Xodim\s+kerak\s*:?/iu', $first)) {
                array_shift($clean);
            }
        }

        // If strict and it doesn't contain required marker, skip. Otherwise don't skip.
        $shouldSkip = $strict ? !$containsTitle : false;

        // Collapse multiple empty lines
        $clean = $this->squeezeEmptyLines($clean);
        $desc = trim(implode("\n", $clean));

        return [$title, $desc, $shouldSkip];
    }

    private function trimEmptyLines(array $lines): array
    {
        // Trim leading empties
        while (!empty($lines) && trim((string) $lines[0]) === '') { array_shift($lines); }
        // Trim trailing empties
        while (!empty($lines) && trim((string) end($lines)) === '') { array_pop($lines); }
        return $lines;
    }

    private function squeezeEmptyLines(array $lines): array
    {
        $out = [];
        $prevEmpty = false;
        foreach ($lines as $ln) {
            $isEmpty = trim((string) $ln) === '';
            if ($isEmpty) {
                if ($prevEmpty) { continue; }
                $prevEmpty = true;
            } else {
                $prevEmpty = false;
            }
            $out[] = $ln;
        }
        return $out;
    }

    private function buildMessageUrl(TelegramChannel $target, int $messageId): ?string
    {
        if (!$messageId) return null;
        $username = $target->username ? ltrim((string) $target->username, '@') : null;
        if ($username) return 'https://t.me/'.$username.'/'.$messageId;
        $cid = (string) ($target->channel_id ?? '');
        if (str_starts_with($cid, '-100')) return 'https://t.me/c/'.substr($cid, 4).'/'.$messageId;
        if ($cid !== '' && str_starts_with($cid, 'http')) {
            if (preg_match('~t\.me/c/(\d+)/~', $cid, $m)) return 'https://t.me/c/'.$m[1].'/'.$messageId;
            if (preg_match('~t\.me/([A-Za-z0-9_]+)/~', $cid, $m)) return 'https://t.me/'.$m[1].'/'.$messageId;
        }
        if ($cid !== '') return 'https://t.me/'.ltrim($cid, '@').'/'.$messageId;
        return null;
    }

    private function parseFloodWait(string $message): int
    {
        if (preg_match('/FLOOD_WAIT_(\d+)/', $message, $m)) return (int) $m[1];
        if (preg_match('/Too many requests: retry in (\d+) seconds?/i', $message, $m)) return (int) $m[1];
        return 0;
    }

    private function getChatId(TelegramChannel $ch): int
    {
        $cid = (string) ($ch->channel_id ?? '');
        if (str_starts_with($cid, '-')) return (int) $cid;
        return crc32($cid);
    }

    private function acquireRateSlots(int $chatId): bool
    {
        $global = (int) config('telegramchannel.global_rps', 15);
        $perChat = (int) config('telegramchannel.per_chat_rps', 1);
        $now = time();
        $redis = app('redis')->connection();
        $gkey = 'tg:rate:global:'.$now;
        $ckey = 'tg:rate:chat:'.$chatId.':'.$now;
        $g = (int) $redis->incr($gkey);
        if ($g === 1) $redis->expire($gkey, 1);
        $c = (int) $redis->incr($ckey);
        if ($c === 1) $redis->expire($ckey, 1);
        if ($g > $global || $c > $perChat) return false;
        return true;
    }
}
