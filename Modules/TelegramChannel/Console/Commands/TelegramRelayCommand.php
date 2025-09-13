<?php

namespace Modules\TelegramChannel\Console\Commands;

use App\Models\TelegramChannel;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Logger;

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

                        if ($mode === 'copy') {
                            // Send text as new message (no source attribution)
                            $API->messages->sendMessage([
                                'peer' => $targetPeer,
                                'message' => (string) $msg['message'],
                            ]);
                        } else {
                            // Forward (shows Forwarded from ...)
                            $API->messages->forwardMessages([
                                'from_peer' => $sourcePeer,
                                'id' => [$mid],
                                'to_peer' => $targetPeer,
                                'drop_author' => false,
                                'drop_media_captions' => false,
                            ]);
                        }

                        $this->line("Relayed message #{$mid} from {$source->channel_id}");
                        $processedMax = max($processedMax, $mid);
                    }

                    if ($processedMax > $lastId) {
                        $source->last_message_id = $processedMax;
                        $source->save();
                    }
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
