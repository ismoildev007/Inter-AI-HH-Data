<?php

namespace Modules\TelegramChannel\Services\Telegram;

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Logger;
use Illuminate\Support\Facades\Cache;

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
        $lock = Cache::lock('tg:madeline:session', 30);
        $lock->block(10);
        try {
            $this->api = new API($session, $settings);
            $this->api->start();
        } finally {
            optional($lock)->release();
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
}
