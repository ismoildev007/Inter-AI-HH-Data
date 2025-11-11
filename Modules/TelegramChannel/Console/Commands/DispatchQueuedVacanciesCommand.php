<?php

namespace Modules\TelegramChannel\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vacancy;
use Illuminate\Support\Facades\Cache;

class DispatchQueuedVacanciesCommand extends Command
{
    protected $signature = 'telegram:vacancies:dispatch-queued {--limit= : Max records to dispatch}';
    protected $description = 'Dispatch DeliverVacancyJob for vacancies with status=queued (id-ascending)';

    public function handle(): int
    {
        $cfg = (array) config('telegramchannel_relay.dispatch', []);
        $limit = (int) ($this->option('limit') ?: ($cfg['deliver_batch_size'] ?? 50));
        if ($limit <= 0) { $limit = 50; }

        $ids = Vacancy::query()
            ->where('status', Vacancy::STATUS_QUEUED)
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->all();

        $count = 0;
        foreach ($ids as $id) {
            // Optional short lock to reduce duplicate dispatch storms under concurrency
            $lock = Cache::lock('tg:dispatch:v'.$id, 10);
            if (!$lock->get()) { continue; }
            try {
                \Modules\TelegramChannel\Jobs\DeliverVacancyJob::dispatch($id)->onQueue('telegram-deliver');
                $count++;
            } finally {
                optional($lock)->release();
            }
        }

        $this->info("Dispatched {$count} queued vacancy(ies).");
        return self::SUCCESS;
    }
}

