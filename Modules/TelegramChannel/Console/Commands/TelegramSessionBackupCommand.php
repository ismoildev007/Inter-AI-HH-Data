<?php

namespace Modules\TelegramChannel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TelegramSessionBackupCommand extends Command
{
    protected $signature = 'telegram:session:backup {--path=} {--keep=10}';
    protected $description = 'Backup MadelineProto session files (session.madeline*) with retention';

    public function handle(): int
    {
        $sessionPath = (string) ($this->option('path') ?: (config('telegramchannel.session') ?? storage_path('app/telegram/session.madeline')));
        $baseDir = dirname($sessionPath);
        $baseName = basename($sessionPath);

        if (!is_dir($baseDir)) {
            $this->error('Session directory not found: '.$baseDir);
            return self::FAILURE;
        }

        $stamp = now()->format('Ymd-His');
        $backupDir = $baseDir.'/backups/'.$stamp;
        if (!File::makeDirectory($backupDir, 0755, true, true)) {
            $this->error('Failed to create backup directory: '.$backupDir);
            return self::FAILURE;
        }

        $pattern = $baseDir.'/'.$baseName.'*';
        $files = glob($pattern) ?: [];
        $copied = 0;
        foreach ($files as $f) {
            if (is_file($f)) {
                File::copy($f, $backupDir.'/'.basename($f));
                $copied++;
            }
        }
        $this->info("Session backup created: {$backupDir} ({$copied} files)");

        // Retention
        $keep = max(1, (int) $this->option('keep'));
        $all = glob($baseDir.'/backups/*') ?: [];
        rsort($all); // newest first
        $toDelete = array_slice($all, $keep);
        foreach ($toDelete as $dir) {
            File::deleteDirectory($dir);
        }
        if (!empty($toDelete)) {
            $this->line('Old backups removed: '.count($toDelete));
        }

        return self::SUCCESS;
    }
}

