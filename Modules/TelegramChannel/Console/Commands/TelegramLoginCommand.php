<?php

namespace Modules\TelegramChannel\Console\Commands;

use Illuminate\Console\Command;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Logger;

class TelegramLoginCommand extends Command
{
    protected $signature = 'telegram:login
        {--phone= : Phone number with country code (e.g. +99890...)}
        {--code= : One-time code sent by Telegram}
        {--password= : Two-factor password (if enabled)}
        {--qr : Use QR login flow}
        {--session= : Override session path}';
    protected $description = 'Login to Telegram (MadelineProto) using phone/QR and initialize the session file';

    public function handle(): int
    {
        $apiId = (int) (config('telegramchannel.api_id') ?? 0);
        $apiHash = (string) (config('telegramchannel.api_hash') ?? '');
        $session = (string) ($this->option('session') ?: (config('telegramchannel.session') ?? storage_path('app/telegram/session.madeline')));

        if (!$apiId || !$apiHash) {
            $this->error('TG_API_ID/TG_API_HASH is not configured. Please set them in .env and run php artisan config:clear');
            return self::FAILURE;
        }

        $this->info('Starting MadelineProto session: '.$session);

        // Build Settings object (MadelineProto v8+)
        $settings = new Settings;
        $settings->getAppInfo()->setApiId($apiId);
        $settings->getAppInfo()->setApiHash($apiHash);
        $settings->getLogger()->setLevel(Logger::LEVEL_NOTICE);

        try {
            $API = new API($session, $settings);

            $phone = $this->option('phone');
            $useQr = (bool) $this->option('qr');

            if ($phone) {
                // Explicit phone login
                $this->info('Using phone login...');
                $API->phoneLogin($phone);

                $code = $this->option('code') ?: $this->ask('Enter the Telegram code');
                $API->completePhoneLogin(trim($code));

                if ($API->getAuthorization() === API::WAITING_PASSWORD) {
                    $password = $this->option('password') ?: $this->secret('Two‑factor password');
                    $API->complete2faLogin($password);
                }
            } else {
                // Default to QR login, unless user chooses phone interactively
                if (!$useQr) {
                    $choice = $this->choice('Login method', ['QR', 'Phone'], 0);
                    $useQr = ($choice === 'QR');
                }

                if ($useQr) {
                    $this->info('Generating QR code...');

                    // Loop until logged in; show new QR if previous expired
                    while ($API->getAuthorization() !== API::LOGGED_IN) {
                        $qr = $API->qrLogin();

                        if ($qr) {
                            $this->line(PHP_EOL.'Scan this QR with your Telegram app:');
                            $this->line($qr->getQRText(2));

                            // Wait until login or QR expiry; returns new QR on expiry
                            $qr = $qr->waitForLoginOrQrCodeExpiration();
                            if ($API->getAuthorization() === API::WAITING_PASSWORD) {
                                $password = $this->option('password') ?: $this->secret('Two‑factor password');
                                $API->complete2faLogin($password);
                            }
                            // If $qr is not null, it means expired; loop will print new QR
                            continue;
                        }

                        // No QR available: either already logged in or waiting for password
                        if ($API->getAuthorization() === API::WAITING_PASSWORD) {
                            $password = $this->option('password') ?: $this->secret('Two‑factor password');
                            $API->complete2faLogin($password);
                        }

                        // Small pause to avoid busy loop
                        usleep(250_000);
                    }
                } else {
                    // Interactive phone flow
                    $phone = $this->ask('Enter phone number with country code (e.g. +99890...)');
                    $API->phoneLogin($phone);
                    $code = $this->ask('Enter the Telegram code');
                    $API->completePhoneLogin(trim($code));
                    if ($API->getAuthorization() === API::WAITING_PASSWORD) {
                        $password = $this->secret('Two‑factor password');
                        $API->complete2faLogin($password);
                    }
                }
            }

            // Ensure session is serialized and fetch user info
            $me = $API->getSelf();
            $this->info('Logged in as @'.($me['username'] ?? 'unknown').' (ID: '.$me['id'].')');
        } catch (\Throwable $e) {
            $this->error('Login failed: '.$e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
