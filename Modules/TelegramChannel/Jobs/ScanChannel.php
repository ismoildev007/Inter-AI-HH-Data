<?php

namespace Modules\TelegramChannel\Jobs;

use App\Models\TelegramChannel;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ScanChannel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public function __construct(public int $channelDbId)
    {
    }

    public function handle(): void
    {
        Log::info('jobga keldi');
        $channel = TelegramChannel::find($this->channelDbId);
        if (!$channel || !$channel->is_source) {
            return;
        }

        // Prevent overlapping scans per channel
        $lock = Cache::lock('tg:scan:'.$channel->id, 20);
        if (!$lock->get()) {
            return; // another scan in progress
        }

        try {
            Log::info('ScanChannel: start', [
                'channel_db_id' => $channel->id,
                'channel_id' => $channel->channel_id,
                'username' => $channel->username,
                'last_message_id' => $channel->last_message_id,
            ]);
            $api = $this->makeApi();
            $api->start();

            $peer = $this->resolvePeer($api, $channel);
            if (!$peer) {
                Log::warning('ScanChannel: resolvePeer failed', ['channel_db_id' => $channel->id]);
                return;
            }

            $minId = (int) ($channel->last_message_id ?? 0);
            $limit = (int) config('telegramchannel.scan_limit', 20);

            $history = $api->messages->getHistory([
                'peer' => $peer,
                'offset_id' => 0,
                'offset_date' => 0,
                'add_offset' => 0,
                'limit' => $limit,
                'max_id' => 0,
                'min_id' => $minId,
                'hash' => 0,
            ]);

            $messages = $history['messages'] ?? [];
            Log::info('ScanChannel: history', [
                'channel_db_id' => $channel->id,
                'min_id' => $minId,
                'count' => is_array($messages) ? count($messages) : 0,
            ]);
            usort($messages, fn($a, $b) => ($a['id'] ?? 0) <=> ($b['id'] ?? 0));

            $processedMax = $minId;
            $dispatched = 0;
            foreach ($messages as $msg) {
                $mid = (int) ($msg['id'] ?? 0);
                if ($mid <= $minId) continue;

                $text = (string) ($msg['message'] ?? '');
                SendCopyMessage::dispatch($channel->id, $mid, $text, $msg)->onQueue('telegram');
                $processedMax = max($processedMax, $mid);
                $dispatched++;
            }

            Log::info('ScanChannel: dispatched', [
                'channel_db_id' => $channel->id,
                'dispatched' => $dispatched,
                'new_last_id' => $processedMax,
            ]);
            if ($processedMax > $minId) {
                $channel->last_message_id = $processedMax;
                $channel->save();
            }
        } finally {
            optional($lock)->release();
        }
    }

    private function makeApi(): API
    {
        $settings = new Settings;
        $settings->getAppInfo()->setApiId((int) config('telegramchannel.api_id'));
        $settings->getAppInfo()->setApiHash((string) config('telegramchannel.api_hash'));
        return new API((string) config('telegramchannel.session'), $settings);
    }

    private function resolvePeer(API $api, TelegramChannel $channel)
    {
        $id = trim((string) ($channel->channel_id ?? ''));
        $username = trim((string) ($channel->username ?? ''));
        $candidates = [];
        if ($username !== '') {
            $candidates[] = $username;
            $candidates[] = '@'.$username;
            $candidates[] = 'https://t.me/'.$username;
        }
        if ($id !== '') {
            if (str_starts_with($id, 'http')) {
                if (preg_match('~t\.me/c/(\d+)/~', $id, $m)) {
                    $candidates[] = '-100'.$m[1];
                }
                $candidates[] = $id;
            } else {
                $candidates[] = $id;
            }
        }
        $candidates = array_values(array_unique($candidates));
        Log::info('ScanChannel: resolving candidates', [
            'channel_db_id' => $channel->id,
            'candidates' => $candidates,
        ]);
        foreach ($candidates as $cand) {
            try {
                return $api->resolvePeer($cand);
            } catch (\Throwable $e1) {
                try {
                    $info = $api->getInfo($cand);
                    if (is_array($info) && isset($info['bot_api_id'])) {
                        return $info['bot_api_id'];
                    }
                } catch (\Throwable $e2) {
                    Log::warning('ScanChannel: candidate failed', [
                        'channel_db_id' => $channel->id,
                        'candidate' => $cand,
                        'error' => $e2->getMessage(),
                    ]);
                }
            }
        }
        return null;
    }
}
