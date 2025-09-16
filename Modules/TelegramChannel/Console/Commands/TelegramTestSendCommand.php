<?php

namespace Modules\TelegramChannel\Console\Commands;

use App\Models\TelegramChannel;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
// No file logging
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Logger;

class TelegramTestSendCommand extends Command
{
    protected $signature = 'telegram:test-send {--text=Hello from telegram:test-send} {--to= : Override target channel_id/username/url}';
    protected $description = 'Send a test message to the target Telegram channel (MadelineProto userbot)';

    public function handle(): int
    {
        $apiId = (int) (config('telegramchannel.api_id') ?? 0);
        $apiHash = (string) (config('telegramchannel.api_hash') ?? '');
        $session = (string) (config('telegramchannel.session') ?? storage_path('app/telegram/session.madeline'));

        if (!$apiId || !$apiHash) {
            $this->error('TG_API_ID/TG_API_HASH is not configured. Set them in .env and php artisan config:clear');
            return self::FAILURE;
        }

        $override = trim((string) ($this->option('to') ?? ''));
        if ($override !== '') {
            $target = new TelegramChannel();
            if (Str::startsWith($override, 'http') || Str::startsWith($override, '@') || !ctype_digit(ltrim($override, '-'))) {
                $target->username = $override;
                $target->channel_id = $override;
            } else {
                $target->channel_id = $override;
            }
        } else {
            $target = TelegramChannel::query()->where('is_target', true)->first();
            if (!$target) {
                $this->error('No target channel found (is_target = true). Add one via web/API or use --to=');
                return self::FAILURE;
            }
        }

        $text = (string) ($this->option('text') ?? 'Hello from telegram:test-send');
        if (trim($text) === '') { $text = 'Hello from telegram:test-send'; }

        // Build settings (reduced verbosity)
        $settings = new Settings;
        $settings->getAppInfo()->setApiId($apiId);
        $settings->getAppInfo()->setApiHash($apiHash);
        $settings->getLogger()->setLevel(Logger::LEVEL_WARNING);

        try {
            $API = new API($session, $settings);
            $API->start();
            $this->line('Test: API started');

            $peer = $this->resolvePeer($API, $target);
            if (!$peer) {
                $this->error('Could not resolve target peer. Make sure your account joined/owns the channel.');
                return self::FAILURE;
            }

            $this->line('Sending test message...');
            $result = $API->messages->sendMessage([
                'peer' => $peer,
                'message' => $text,
            ]);

            $newId = $this->extractMessageId($result);
            $url = $this->buildMessageUrl($target, $newId);
            $this->info('Sent. New message id: '.($newId ?: 'unknown'));
            if ($url) { $this->info('URL: '.$url); }
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Test send failed: '.$e->getMessage());
            return self::FAILURE;
        }
    }

    private function resolvePeer(API $api, TelegramChannel $channel)
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
                    try { $api->messages->importChatInvite(['hash' => $m[1]]); } catch (\Throwable $e) {}
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
            try { return $api->resolvePeer($cand); } catch (\Throwable $e1) {
                try { $info = $api->getInfo($cand); if (is_array($info) && isset($info['bot_api_id'])) { return $info['bot_api_id']; } } catch (\Throwable $e2) {}
            }
        }
        if ($id !== '' && Str::startsWith($id, '-100') && ctype_digit(ltrim($id, '-'))) { return (int) $id; }
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
}
