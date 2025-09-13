<?php

namespace Modules\TelegramChannel\Console\Commands;

use App\Models\TelegramChannel;
use App\Models\Vacancy;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Logger;
use Illuminate\Support\Facades\Log;

class TelegramRelayCommand extends Command
{
    protected $signature = 'telegram:relay {--once : Run one pass and exit} {--sleep=5 : Seconds to sleep between passes}';
    protected $description = 'Relay new text messages from source channels to target channel (userbot via MadelineProto)';

    public function handle(): int
    {
        $apiId = (int) (config('telegramchannel.api_id') ?? 0);
        $apiHash = (string) (config('telegramchannel.api_hash') ?? '');
        $session = (string) (config('telegramchannel.session') ?? storage_path('app/telegram/session.madeline'));
        $mode = (string) (config('telegramchannel.relay_mode', 'forward'));
        $textOnly = (bool) config('telegramchannel.text_only', true);

        if (!$apiId || !$apiHash) {
            $this->error('TG_API_ID/TG_API_HASH is not configured. Please set them in .env and run php artisan config:clear');
            return self::FAILURE;
        }

        $sources = TelegramChannel::query()->where('is_source', true)->get();
        $target = TelegramChannel::query()->where('is_target', true)->first();

        if (!$target) {
            $this->error('No target channel configured (is_target = true). Add one via POST /api/v1/telegram/channels');
            return self::FAILURE;
        }

        $this->info("Relay mode={$mode}, textOnly=".($textOnly ? 'true' : 'false'));
        $this->line('Build: save-v1');
        $this->line('Sources: '.($sources->pluck('channel_id')->implode(', ') ?: 'none'));
        $this->line('Target: '.($target->channel_id ?: $target->username ?: 'unknown'));

        // Build Settings object (MadelineProto v8+)
        $settings = new Settings;
        $settings->getAppInfo()->setApiId($apiId);
        $settings->getAppInfo()->setApiHash($apiHash);
        $settings->getLogger()->setLevel(Logger::LEVEL_VERBOSE);

        try {
            $API = new API($session, $settings);
            $API->start();
        } catch (\Throwable $e) {
            $this->error('Failed to start MadelineProto: '.$e->getMessage());
            return self::FAILURE;
        }

        $runOnce = (bool) $this->option('once');
        $sleep = (int) $this->option('sleep');

        do {
            try {
                // Resolve target peer
                $targetPeer = $this->resolvePeer($API, $target);
                if (!$targetPeer) {
                    $this->warn('Target peer not resolvable yet. Ensure your account joined/owns the channel.');
                    if ($runOnce) { return self::FAILURE; }
                    sleep($sleep);
                    continue;
                }

                foreach ($sources as $source) {
                    $sourcePeer = $this->resolvePeer($API, $source);
                    if (!$sourcePeer) {
                        $this->warn('Skip unresolved source: '.$source->channel_id);
                        continue;
                    }

                    // Fetch recent messages (pull-based)
                    $lastId = (int) ($source->last_message_id ?? 0);
                    $limit = 20;
                    $history = $API->messages->getHistory([
                        'peer' => $sourcePeer,
                        'offset_id' => 0,
                        'offset_date' => 0,
                        'add_offset' => 0,
                        'limit' => $limit,
                        'max_id' => 0,
                        'min_id' => $lastId,
                        'hash' => 0,
                    ]);

                    $messages = $history['messages'] ?? [];
                    // Ensure ascending by ID
                    usort($messages, fn($a, $b) => ($a['id'] ?? 0) <=> ($b['id'] ?? 0));

                    $processedMax = $lastId;
                    foreach ($messages as $msg) {
                        $mid = (int) ($msg['id'] ?? 0);
                        if ($mid <= $lastId) {
                            continue;
                        }

                        // Skip service messages, media (if textOnly)
                        $hasText = isset($msg['message']) && is_string($msg['message']) && strlen(trim($msg['message'])) > 0;
                        $hasMedia = isset($msg['media']) && !empty($msg['media']);
                        if ($textOnly && (!$hasText || $hasMedia)) {
                            $processedMax = max($processedMax, $mid);
                            continue;
                        }

                        // Normalize for special patterns and strip signatures
                        $origText = (string) ($msg['message'] ?? '');
                        [$vacTitle, $descText, $shouldSkip] = $this->processJobPost($origText);
                        if ($shouldSkip) {
                            $this->line('Skip by channel-specific rule, mid='.$mid);
                            $processedMax = max($processedMax, $mid);
                            continue;
                        }

                        // Duplicate description policy (normalized)
                        if ($descText !== '') {
                            $publishExists = Vacancy::where('description', $descText)
                                ->where('status', \App\Models\Vacancy::STATUS_PUBLISH)
                                ->exists();
                            if ($publishExists) {
                                $this->line('Duplicate publish description detected, skipping relay/save for mid='.$mid);
                                $processedMax = max($processedMax, $mid);
                                continue;
                            }
                        }

                        $result = null;
                        $this->line("About to relay (mode={$mode}) mid={$mid} text_len=".strlen((string)($msg['message'] ?? '')));
                        if ($mode === 'copy') {
                            // Send normalized message (no source attribution)
                            $outText = $vacTitle ? ($vacTitle.":\n".$descText) : $descText;
                            $result = $API->messages->sendMessage([
                                'peer' => $targetPeer,
                                'message' => $outText,
                            ]);
                        } else {
                            // Forward (shows Forwarded from ...)
                            $result = $API->messages->forwardMessages([
                                'from_peer' => $sourcePeer,
                                'id' => [$mid],
                                'to_peer' => $targetPeer,
                                'drop_author' => false,
                                'drop_media_captions' => false,
                            ]);
                        }

                        // Try to extract new message id in target
                        $newId = $this->extractMessageId($result);
                        if (!$newId) {
                            // Fallback: fetch latest message id from target
                            try {
                                $latest = $API->messages->getHistory([
                                    'peer' => $targetPeer,
                                    'offset_id' => 0,
                                    'offset_date' => 0,
                                    'add_offset' => 0,
                                    'limit' => 1,
                                    'max_id' => 0,
                                    'min_id' => 0,
                                    'hash' => 0,
                                ]);
                                $latestMsg = $latest['messages'][0] ?? null;
                                $newId = (int) ($latestMsg['id'] ?? 0);
                            } catch (\Throwable $e) {
                                $newId = 0;
                            }
                        }

                        // Save into vacancies with apply_url pointing to target message URL
                        try {
                            $applyUrl = $this->buildMessageUrl($target, $newId);
                            $external = 'telegram:'.($source->channel_id ?? 'unknown').':'.$mid;
                            $this->line('Saving vacancy external_id='.$external.' newId='.$newId.' apply_url='.(string)($applyUrl ?? 'null'));
                            Log::info('Vacancy save attempt', ['external_id' => $external, 'apply_url' => $applyUrl, 'new_id' => $newId]);
                            $ttl = (int) config('telegramchannel.vacancy_ttl_seconds', 604800);
                            $expDate = null;
                            if ($ttl >= 86400) {
                                $days = (int) ceil($ttl / 86400);
                                $expDate = now()->addDays($days)->toDateString();
                            }
                            $vac = Vacancy::updateOrCreate(
                                ['external_id' => $external],
                                [
                                    'source' => 'Telegram',
                                    'title' => $vacTitle ?: null,
                                    'description' => $descText,
                                    'apply_url' => $applyUrl,
                                    'status' => \App\Models\Vacancy::STATUS_PUBLISH,
                                    // For weekly (or longer) TTL we use date-based expies_at; for <1 day TTL we rely on created_at
                                    'expies_at' => $expDate,
                                    'raw_data' => json_encode($msg, JSON_UNESCAPED_UNICODE),
                                ]
                            );
                            $this->line('Saved vacancy #'.$vac->id.' apply_url='.($applyUrl ?? 'null'));
                            Log::info('Vacancy saved', ['id' => $vac->id]);
                        } catch (\Throwable $e) {
                            $this->warn('Vacancy save failed: '.$e->getMessage());
                            Log::error('Vacancy save failed', ['error' => $e->getMessage()]);
                        }

                        $this->line("Relayed message #{$mid} from {$source->channel_id}");
                        $processedMax = max($processedMax, $mid);
                    }

                    if ($processedMax > $lastId) {
                        $source->last_message_id = $processedMax;
                        $source->save();
                    }
                }

                // Mark expired vacancies as archived (do not delete)
                try {
                    $ttl = (int) config('telegramchannel.vacancy_ttl_seconds', 604800);
                    if ($ttl < 86400) {
                        $purged = Vacancy::where('status', '!=', \App\Models\Vacancy::STATUS_ARCHIVE)
                            ->where('created_at', '<=', now()->subSeconds(max(1, $ttl)))
                            ->update(['status' => \App\Models\Vacancy::STATUS_ARCHIVE]);
                    } else {
                        $purged = Vacancy::where('status', '!=', \App\Models\Vacancy::STATUS_ARCHIVE)
                            ->whereNotNull('expies_at')
                            ->where('expies_at', '<=', now()->toDateString())
                            ->update(['status' => \App\Models\Vacancy::STATUS_ARCHIVE]);
                    }
                    if (!empty($purged)) {
                        $this->line('Archived expired vacancies: '.$purged);
                    }
                } catch (\Throwable $e) {
                    $this->warn('Purge failed: '.$e->getMessage());
                }
            } catch (\Throwable $e) {
                $this->error('Relay loop error: '.$e->getMessage());
            }

            if (!$runOnce) {
                sleep($sleep);
            }
        } while (!$runOnce);

        $this->info('Relay finished.');
        return self::SUCCESS;
    }

    /**
     * Process job post text: extract title (e.g., "Xodim kerak"),
     * remove signature lines (e.g., "👉 @UstozShogird kanaliga ulanish"), and
     * decide if this post should be skipped for channel-specific rules.
     *
     * @return array{0:?string,1:string,2:bool} [$title, $description, $shouldSkip]
     */
    private function processJobPost(string $text): array
    {
        $lines = preg_split('/\R/', $text);
        $clean = [];
        foreach ($lines as $line) {
            $ln = rtrim((string) $line);
            // Strip signature lines like: "👉 @UstozShogird kanaliga ulanish"
            if (preg_match('/@UstozShogird/i', $ln)) {
                continue;
            }
            $clean[] = $ln;
        }

        // Remove leading/trailing blank lines
        $clean = $this->trimEmptyLines($clean);

        // Determine if this is a job post we want (must contain "Xodim kerak")
        $containsTitle = (bool) preg_match('/Xodim\s+kerak/iu', $text);
        $title = $containsTitle ? 'Xodim kerak' : null;

        // If the first non-empty line itself is a title, drop it from description
        if (!empty($clean)) {
            $first = ltrim((string) $clean[0]);
            if (preg_match('/^Xodim\s+kerak\s*:?/iu', $first)) {
                array_shift($clean);
            }
        }

        // If it doesn't contain the required marker at all, skip
        $shouldSkip = !$containsTitle;

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

    private function extractMessageId($result): int
    {
        $id = 0;
        $walker = function ($node) use (&$walker, &$id) {
            if ($id) { return; }
            if (is_array($node)) {
                if (isset($node['message']) && is_array($node['message']) && isset($node['message']['id'])) {
                    $id = (int) $node['message']['id'];
                    return;
                }
                if (isset($node['id']) && is_int($node['id'])) {
                    $id = (int) $node['id'];
                    return;
                }
                foreach ($node as $v) { $walker($v); if ($id) return; }
            }
        };
        $walker($result);
        return $id;
    }

    private function buildMessageUrl(TelegramChannel $target, int $messageId): ?string
    {
        if (!$messageId) { return null; }
        $username = $target->username ? ltrim($target->username, '@') : null;
        if ($username) {
            return 'https://t.me/'.$username.'/'.$messageId;
        }
        $cid = (string) ($target->channel_id ?? '');
        if (str_starts_with($cid, '-100')) {
            return 'https://t.me/c/'.substr($cid, 4).'/'.$messageId;
        }
        if ($cid !== '' && str_starts_with($cid, 'http')) {
            if (preg_match('~t\.me/c/(\d+)/~', $cid, $m)) {
                return 'https://t.me/c/'.$m[1].'/'.$messageId;
            }
            if (preg_match('~t\.me/([A-Za-z0-9_]+)/~', $cid, $m)) {
                return 'https://t.me/'.$m[1].'/'.$messageId;
            }
        }
        if ($cid !== '') {
            return 'https://t.me/'.ltrim($cid, '@').'/'.$messageId;
        }
        return null;
    }

    private function resolvePeer(\danog\MadelineProto\API $API, TelegramChannel $channel)
    {
        $id = trim((string) ($channel->channel_id ?? ''));
        $username = trim((string) ($channel->username ?? ''));

        $candidates = [];

        if ($username && !Str::startsWith($username, 'http')) {
            $candidates[] = $username;
            if ($username[0] !== '@') {
                $candidates[] = '@'.$username;
            }
            $candidates[] = 'https://t.me/'.ltrim($username, '@');
        }

        if ($id !== '') {
            if (Str::startsWith($id, 'http')) {
                if (preg_match('~t\.me/\+([A-Za-z0-9_-]+)$~', $id, $m)) {
                    try { $API->messages->importChatInvite(['hash' => $m[1]]); } catch (\Throwable $e) {}
                }
                if (preg_match('~t\.me/c/(\d+)/(\d+)~', $id, $m)) {
                    $candidates[] = '-100'.$m[1];
                }
                $candidates[] = $id;
            } else {
                $candidates[] = $id;
                if (!Str::startsWith($id, '-100') && !ctype_digit(str_replace('-', '', $id))) {
                    $candidates[] = '@'.$id;
                    $candidates[] = 'https://t.me/'.ltrim($id, '@');
                }
            }
        }

        $candidates = array_values(array_unique($candidates));
        $this->line('Resolving candidates: '.implode(', ', $candidates));

        foreach ($candidates as $cand) {
            try {
                return $API->resolvePeer($cand);
            } catch (\Throwable $e) {
                // Try getInfo fallback to obtain bot_api_id
                try {
                    $info = $API->getInfo($cand);
                    if (is_array($info) && isset($info['bot_api_id'])) {
                        return $info['bot_api_id'];
                    }
                } catch (\Throwable $e2) {
                    // try next candidate
                }
            }
        }

        // Last resort: numeric -100... cast
        if ($id !== '' && Str::startsWith($id, '-100') && ctype_digit(ltrim($id, '-'))) {
            return (int) $id;
        }

        return null;
    }
}
