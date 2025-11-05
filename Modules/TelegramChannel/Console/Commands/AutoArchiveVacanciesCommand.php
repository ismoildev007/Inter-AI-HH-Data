<?php

namespace Modules\TelegramChannel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Vacancy;

class AutoArchiveVacanciesCommand extends Command
{
    protected $signature = 'telegram:vacancies:auto-archive {--dry : Show counts only, do not update}';
    protected $description = 'Auto-archive published vacancies older than configured days';

    public function handle(): int
    {
        $days = (int) config('telegramchannel_relay.dedupe.auto_archive_days', 30);
        if ($days <= 0) {
            $this->warn('Auto-archive disabled (days <= 0).');
            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);
        $q = Vacancy::query()->where('status', 'publish')->where('created_at', '<', $cutoff);
        $count = (clone $q)->count();
        $this->info("Eligible for archive (>{$days} days): {$count}");

        if ($this->option('dry')) {
            return self::SUCCESS;
        }

        $updated = $q->update(['status' => 'archive']);
        $this->info("Archived rows: {$updated}");
        Log::info('TelegramVacancies auto-archive', ['days' => $days, 'archived' => $updated]);
        return self::SUCCESS;
    }
}
