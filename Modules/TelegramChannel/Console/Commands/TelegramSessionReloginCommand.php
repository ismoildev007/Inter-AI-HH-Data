<?php

namespace Modules\TelegramChannel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TelegramSessionReloginCommand extends Command
{
    protected $signature = 'telegram:session:relogin {--force : Do not ask for confirmation} {--path=}';
    protected $description = 'Backup and remove session files to force a fresh login (run telegram:login afterwards)';

    public function handle(): int
    {
        $sessionPath = (string) ($this->option('path') ?: (config('telegramchannel.session') ?? storage_path('app/telegram/session.madeline')));
        $baseDir = dirname($sessionPath);
        $baseName = basename($sessionPath);

        if (!$this->option('force') && !$this->confirm('This will backup and remove session files, continue?')) {
            $this->warn('Aborted.');
            return self::SUCCESS;
        }

        // Backup first
        $this->call('telegram:session:backup', ['--path' => $sessionPath]);

        $pattern = $baseDir.'/'.$baseName.'*';
        $files = glob($pattern) ?: [];
        $removed = 0;
        foreach ($files as $f) {
            if (is_file($f)) {
                @unlink($f);
                $removed++;
            }
        }
        $this->info("Removed {$removed} session files from {$baseDir}");
        $this->line('Next: run php artisan telegram:login to initialize a fresh session.');
        return self::SUCCESS;
    }
}

