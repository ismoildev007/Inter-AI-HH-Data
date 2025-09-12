<?php

namespace Modules\TelegramChannel\Console\Commands;

use Illuminate\Console\Command;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Logger;

class TelegramLoginCommand extends Command
{
    protected $signature = 'telegram:login';
    protected $description = 'Login to Telegram (MadelineProto) and initialize the session file';

    public function handle(): int
    {
        $apiId = (int) (config('telegramchannel.api_id') ?? 0);
        $apiHash = (string) (config('telegramchannel.api_hash') ?? '');
        $session = (string) (config('telegramchannel.session') ?? storage_path('app/telegram/session.madeline'));

        if (!$apiId || !$apiHash) {
            $this->error('TG_API_ID/TG_API_HASH is not configured. Please set them in .env and run php artisan config:clear');
            return self::FAILURE;
        }

        $this->info('Starting MadelineProto session: '.$session);

        // Build Settings object (MadelineProto v8+)
        $settings = new Settings;
        $settings->getAppInfo()->setApiId($apiId);
        $settings->getAppInfo()->setApiHash($apiHash);
        $settings->getLogger()->setLevel(Logger::LEVEL_VERBOSE);

        try {
            $API = new API($session, $settings);
            $API->start();
            $me = $API->getSelf();
            $this->info('Logged in as @'.($me['username'] ?? 'unknown').' (ID: '.$me['id'].')');
        } catch (\Throwable $e) {
            $this->error('Login failed: '.$e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
