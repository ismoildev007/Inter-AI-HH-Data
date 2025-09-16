<?php

namespace Modules\TelegramChannel\Console\Commands;

use App\Models\TelegramChannel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
// No file logging
use Illuminate\Support\Str;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Logger;
use Modules\TelegramChannel\Jobs\SendCopyMessage;

class TelegramScanLoopCommand extends Command
{
    protected $signature = 'telegram:scan-loop {--sleep=15 : Seconds to sleep between passes} {--limit=0 : Max channels per pass}';
    protected $description = 'Continuously dispatch scan jobs for source channels (daemon-style)';

    public function handle(): int
    {
        $sleep = (int) $this->option('sleep');
        if ($sleep <= 0) { $sleep = (int) config('telegramchannel.scan_interval_seconds', 15); }

        // Prepare MadelineProto settings once; API wrapper will be reused across passes
        $apiId = (int) (config('telegramchannel.api_id') ?? 0);
        $apiHash = (string) (config('telegramchannel.api_hash') ?? '');
        $session = (string) (config('telegramchannel.session') ?? storage_path('app/telegram/session.madeline'));
        if (!$apiId || !$apiHash) {
            $this->error('TG_API_ID/TG_API_HASH is not configured.');
            return self::FAILURE;
        }

        $this->info('Scan loop started (sleep='.$sleep.'s)');

        // We will build settings per pass (low verbosity) and create API per pass
        // This avoids long-lived IPC sessions while still reducing log shovqin

        // Main loop
        while (true) {
            // Global lock to avoid race with sender
            $lock = Cache::lock('tg:api:lock', 30);
            try {
                $lock->block(10);
                try {
                    // Build settings & API for this pass only (lower verbosity)
                    $settings = new Settings;
                    $settings->getAppInfo()->setApiId($apiId);
                    $settings->getAppInfo()->setApiHash($apiHash);
                    $settings->getLogger()->setLevel(Logger::LEVEL_WARNING);
                    $API = new API($session, $settings);
                    $API->start();
                    $this->line('scan-loop: API started');

                    // Sharding: 100+ source uchun har passda faqat 1 shardni skan qilamiz
                    $shards = max(1, (int) config('telegramchannel.scan_shards', 10));
                    $cursorKey = 'tg:scan:shard_idx';
                    if (!Cache::has($cursorKey)) { Cache::put($cursorKey, 0, now()->addDay()); }
                    $shardIdx = Cache::increment($cursorKey) % $shards;
                    $this->line('scan-loop: shard index='.$shardIdx.' of '.$shards);

                    // Idlar ro'yxatini olib, shard bo'yicha filterlaymiz (DB portable)
                    $allIds = TelegramChannel::query()->where('is_source', true)->orderBy('id')->pluck('id')->all();
                    $ids = [];
                    foreach ($allIds as $id) { if (((int)$id % $shards) === (int)$shardIdx) { $ids[] = $id; } }
                    $limitOpt = (int) $this->option('limit');
                    if ($limitOpt > 0) { $ids = array_slice($ids, 0, $limitOpt); }
                    $channels = TelegramChannel::whereIn('id', $ids)->orderBy('id')->get();
                    $this->line('scan-loop: candidates='.count($ids).', channels='.count($channels));

                    $totalDispatched = 0;
                    $scannedChannels = 0;
                    foreach ($channels as $channel) {
                        // Per-channel lock (avoid concurrent passes)
                        $chLock = Cache::lock('tg:scan:'.$channel->id, 10);
                        if (!$chLock->get()) { continue; }
                        try {
                            $peer = $this->resolvePeer($API, $channel);
                            if (!$peer) { Log::warning('scan-loop: resolvePeer failed', ['channel_id' => $channel->id]); continue; }

                            $minId = (int) ($channel->last_message_id ?? 0);
                            $limitMsgs = (int) config('telegramchannel.scan_limit', 20);
                            $history = $API->messages->getHistory([
                                'peer' => $peer,
                                'offset_id' => 0,
                                'offset_date' => 0,
                                'add_offset' => 0,
                                'limit' => $limitMsgs,
                                'max_id' => 0,
                                'min_id' => $minId,
                                'hash' => 0,
                            ]);
                            $messages = $history['messages'] ?? [];
                            usort($messages, fn($a, $b) => ($a['id'] ?? 0) <=> ($b['id'] ?? 0));

                            $processedMax = $minId;
                            $dispatched = 0;
                            foreach ($messages as $msg) {
                                $mid = (int) ($msg['id'] ?? 0);
                                if ($mid <= $minId) continue;
                                $text = (string) ($msg['message'] ?? '');
                                SendCopyMessage::dispatch($channel->id, $mid, $text, $msg)
                                    ->onQueue(config('telegramchannel.send_queue', 'telegram'));
                                $processedMax = max($processedMax, $mid);
                                $dispatched++;
                                $totalDispatched++;
                            }
                            if ($processedMax > $minId) {
                                $channel->last_message_id = $processedMax;
                                $channel->save();
                            }
                            $scannedChannels++;
                        } catch (\Throwable $e) {
                            $this->warn('scan-loop pass error: '.$e->getMessage());
                        } finally {
                            optional($chLock)->release();
                        }
                    }
                    $this->line('Shard '.$shardIdx.'/'.$shards.' scanned channels: '.$scannedChannels.'; dispatched messages: '.$totalDispatched);
                } finally {
                    optional($lock)->release();
                }
            } catch (\Throwable $e) {
                $this->error('Scan loop error: '.$e->getMessage());
            }

            sleep($sleep);
        }

        return self::SUCCESS;
    }

    private function resolvePeer(API $API, TelegramChannel $channel)
    {
        $id = trim((string) ($channel->channel_id ?? ''));
        $username = trim((string) ($channel->username ?? ''));

        $candidates = [];

        if ($username !== '' && !Str::startsWith($username, 'http')) {
            $candidates[] = $username;
            if ($username[0] !== '@') { $candidates[] = '@'.$username; }
            $candidates[] = 'https://t.me/'.ltrim($username, '@');
        }

        if ($id !== '') {
            if (Str::startsWith($id, 'http')) {
                if (preg_match('~t\.me/\+([A-Za-z0-9_-]+)$~', $id, $m)) {
                    try { $API->messages->importChatInvite(['hash' => $m[1]]); } catch (\Throwable $e) {}
                }
                if (preg_match('~t\.me/c/(\d+)/(?:\d+)~', $id, $m)) { $candidates[] = '-100'.$m[1]; }
                $candidates[] = $id;
            } else {
                $candidates[] = $id;
                if (!Str::startsWith($id, '-100') && !ctype_digit(str_replace('-', '', $id))) {
                    $candidates[] = '@'.ltrim($id, '@');
                    $candidates[] = 'https://t.me/'.ltrim($id, '@');
                }
            }
        }

        $candidates = array_values(array_unique($candidates));
        foreach ($candidates as $cand) {
            try { return $API->resolvePeer($cand); } catch (\Throwable $e1) {
                try { $info = $API->getInfo($cand); if (is_array($info) && isset($info['bot_api_id'])) { return $info['bot_api_id']; } } catch (\Throwable $e2) {}
            }
        }
        if ($id !== '' && Str::startsWith($id, '-100') && ctype_digit(ltrim($id, '-'))) { return (int) $id; }
        return null;
    }
}
