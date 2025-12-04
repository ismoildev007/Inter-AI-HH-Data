<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create database backup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info('🚀 Database backup boshlandi...');

            $database = config('database.connections.' . config('database.default') . '.database');
            $username = config('database.connections.' . config('database.default') . '.username');
            $password = config('database.connections.' . config('database.default') . '.password');
            $host = config('database.connections.' . config('database.default') . '.host');

            // Backup fayl nomi
            $filename = 'backup_' . Carbon::now()->format('Y_m_d_His') . '.sql';
            $backupPath = storage_path('app/backups');

            // Backup papkani yaratish
            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }

            $fullPath = $backupPath . '/' . $filename;

            // PostgreSQL uchun
            if (config('database.default') === 'pgsql') {
                $command = sprintf(
                    'PGPASSWORD="%s" pg_dump -h %s -U %s %s > %s',
                    $password,
                    $host,
                    $username,
                    $database,
                    $fullPath
                );
            }
            // MySQL uchun
            else {
                $command = sprintf(
                    'mysqldump -h %s -u %s -p%s %s > %s',
                    $host,
                    $username,
                    $password,
                    $database,
                    $fullPath
                );
            }

            // Backup yaratish
            exec($command, $output, $returnVar);

            if ($returnVar === 0 && file_exists($fullPath)) {
                $fileSize = filesize($fullPath);
                $this->info("✅ Backup muvaffaqiyatli yaratildi: {$filename}");
                $this->info("📦 Fayl hajmi: " . $this->formatBytes($fileSize));

                // Log::info('✅ Database backup muvaffaqiyatli yaratildi', [
                //     'filename' => $filename,
                //     'size' => $fileSize,
                //     'path' => $fullPath
                // ]);

                // Eski backuplarni o'chirish (30 kundan eski)
                $this->cleanOldBackups($backupPath);

                return Command::SUCCESS;
            } else {
                $this->error('❌ Backup yaratishda xatolik yuz berdi!');
                Log::error('❌ Database backup xatolik', [
                    'return_code' => $returnVar,
                    'output' => $output
                ]);

                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error('❌ Xatolik: ' . $e->getMessage());
            Log::error('❌ Database backup exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Eski backuplarni o'chirish
     */
    private function cleanOldBackups($backupPath)
    {
        $files = glob($backupPath . '/backup_*.sql');
        $now = time();
        $deletedCount = 0;

        foreach ($files as $file) {
            // 30 kundan eski fayllarni o'chirish
            if (is_file($file) && ($now - filemtime($file)) >= (30 * 24 * 3600)) {
                unlink($file);
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            $this->info("🗑️  {$deletedCount} ta eski backup o'chirildi");
            //Log::info("🗑️  Eski backuplar o'chirildi", ['count' => $deletedCount]);
        }
    }

    /**
     * Bytes ni o'qish oson formatga o'tkazish
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
