<?php

namespace Modules\TelegramChannel\Services\Telegram;

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Logger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Modules\TelegramChannel\Exceptions\SessionLockBusyException;

    class MadelineClient
    {
        private API $api;

    public function __construct()
    {
        $apiId  = (int) (config('telegramchannel.api_id') ?? 0);
        $apiHash= (string) (config('telegramchannel.api_hash') ?? '');
        $session= (string) (config('telegramchannel.session') ?? storage_path('app/telegram/session.madeline'));

        $settings = new Settings;
        $settings->getAppInfo()->setApiId($apiId);
        $settings->getAppInfo()->setApiHash($apiHash);
        $settings->getLogger()->setLevel(Logger::LEVEL_ERROR);

        // Prevent concurrent starts on the same session file (avoid corruption)
        $lockTtl = (int) config('telegramchannel_relay.locks.session_ttl', 120);
        $lockWait = (int) config('telegramchannel_relay.locks.session_wait', 45);
        $lock = Cache::lock('tg:madeline:session', max(1, $lockTtl));
        $hasLock = false;
        try {
            $lock->block(max(1, $lockWait));
            $hasLock = true;
        } catch (LockTimeoutException $e) {
            throw new SessionLockBusyException('Madeline session lock busy', 0, $e);
        }
        try {
            $api = new API($session, $settings);
            $api->start();
            $this->api = $api;
        } finally {
            if ($hasLock) {
                optional($lock)->release();
            }
        }

        // Optional: log memory right after start for diagnostics
        if ((bool) config('telegramchannel_relay.debug.log_memory', false)) {
            $usage = round(memory_get_usage(true) / 1048576, 1);
            $peak  = round(memory_get_peak_usage(true) / 1048576, 1);
            \Log::debug('MadelineClient started', [
                'usage_mb' => $usage,
                'peak_mb'  => $peak,
                'session'  => $session,
            ]);
        }
    }

    public function getHistory(string $peer, int $minId, int $limit = 100): array
    {
        return $this->api->messages->getHistory([
            'peer'        => $peer,
            'offset_id'   => 0,
            'offset_date' => 0,
            'add_offset'  => 0,
            'limit'       => $limit,
            'max_id'      => 0,
            'min_id'      => $minId, // faqat yangi id lar
            'hash'        => 0,
        ]);
    }

    public function sendMessage(string|int $peer, string $message, ?int $replyToMessageId = null): array
    {
        $params = [
            'peer' => $peer,
            'message' => $message,
            'no_webpage' => true,
            'parse_mode' => 'HTML',
        ];
        if ($replyToMessageId) {
            $params['reply_to'] = [
                '_'=> 'inputReplyToMessage',
                'reply_to_msg_id' => $replyToMessageId,
            ];
        }
        return $this->api->messages->sendMessage($params);
    }

    public function forwardMessage(string|int $fromPeer, string|int $toPeer, int $messageId): array
    {
        return $this->api->messages->forwardMessages([
            'from_peer' => $fromPeer,
            'id'        => [$messageId],
            'to_peer'   => $toPeer,
            'drop_author' => true,
            'drop_media_captions' => false,
        ]);
    }

    /**
     * Soft reset the underlying API using the same session path and settings.
     * Does NOT delete session files; safe to call in production when the client
     * falls into a bad state (e.g., repeated CANCELLED/peer DB issues).
     */
    public function softReset(): void
    {
        $apiId  = (int) (config('telegramchannel.api_id') ?? 0);
        $apiHash= (string) (config('telegramchannel.api_hash') ?? '');
        $session= (string) (config('telegramchannel.session') ?? storage_path('app/telegram/session.madeline'));

        // Prevent concurrent resets
        $lock = \Cache::lock('tg:madeline:reset', 15);
        $lock->block(5);
        try {
            $settings = new Settings;
            $settings->getAppInfo()->setApiId($apiId);
            $settings->getAppInfo()->setApiHash($apiHash);
            $settings->getLogger()->setLevel(Logger::LEVEL_ERROR);

            $api = new API($session, $settings);
            $api->start();
            $this->api = $api;

            if ((bool) config('telegramchannel_relay.debug.log_memory', false)) {
                $usage = round(memory_get_usage(true) / 1048576, 1);
                $peak  = round(memory_get_peak_usage(true) / 1048576, 1);
                // Log::info('MadelineClient soft reset completed', [
                //     'usage_mb' => $usage,
                //     'peak_mb'  => $peak,
                // ]);
            }
        } finally {
            optional($lock)->release();
        }
    }
}
