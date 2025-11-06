<?php

namespace Modules\Interviews\Console\Commands;

use App\Models\Application;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Interviews\Services\InterviewService;

class BackfillDiscardInterviewsCommand extends Command
{
    protected $signature = 'interviews:backfill-discard {--user-id=} {--source=hh} {--dry-run}';
    protected $description = 'Create missing discard interviews from applications in discard-like states';

    public function handle(InterviewService $service): int
    {
        $userId = $this->option('user-id') ? (int) $this->option('user-id') : null;
        $source = (string) $this->option('source');
        $dryRun = (bool) $this->option('dry-run');

        $discardStates = (array) config('interviews.discard_statuses', ['discard']);
        $discardPatterns = (array) config('interviews.discard_patterns', []);

        $apps = Application::query()
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->with('vacancy')
            ->get();

        $total = 0;
        $created = 0;
        foreach ($apps as $app) {
            $state = (string) ($app->status ?? $app->hh_status ?? '');
            if ($state === '') { continue; }

            $isDiscard = in_array($state, $discardStates, true);
            if (!$isDiscard && !empty($discardPatterns)) {
                foreach ($discardPatterns as $pattern) {
                    if ($pattern !== '' && stripos($state, (string) $pattern) !== false) {
                        $isDiscard = true;
                        break;
                    }
                }
            }
            if (!$isDiscard) { continue; }

            if (!$app->vacancy || ($source && $app->vacancy->source !== $source)) {
                continue;
            }

            $total++;
            if ($dryRun) { continue; }

            try {
                $i = $service->ensureDiscardForApplication($app);
                if ($i) { $created++; }
            } catch (\Throwable $e) {
                Log::warning('Backfill discard interview failed', [
                    'application_id' => $app->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Checked applications: {$apps->count()}, discard candidates: {$total}, created: {$created}");
        return self::SUCCESS;
    }
}

