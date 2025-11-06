<?php

namespace Modules\Applications\Console\Commands;

use App\Models\Application;
use App\Models\HhAccount;
use App\Models\Vacancy;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Modules\Users\Repositories\HhAccountRepositoryInterface;
use Modules\Vacancies\Interfaces\HHVacancyInterface;

class SyncHhNegotiationsCommand extends Command
{
    protected $signature = 'hh:sync-negotiations {--per-page=100} {--max-pages=10} {--user-id=}';
    protected $description = 'Sync HH negotiations and update applications.hh_status for users with HH accounts';

    public function handle(): int
    {
        $perPage = (int) $this->option('per-page');
        $maxPages = (int) $this->option('max-pages');
        $filterUserId = $this->option('user-id') ? (int) $this->option('user-id') : null;

        /** @var HHVacancyInterface $hh */
        $hh = app(HHVacancyInterface::class);
        /** @var HhAccountRepositoryInterface $acctRepo */
        $acctRepo = app(HhAccountRepositoryInterface::class);

        $accountsQuery = HhAccount::query()->whereNotNull('access_token');
        if ($filterUserId) {
            $accountsQuery->where('user_id', $filterUserId);
        }

        $updatedCount = 0;
        $scannedCount = 0;

        $accountsQuery->orderBy('id')->chunk(50, function ($accounts) use ($hh, $acctRepo, $perPage, $maxPages, &$updatedCount, &$scannedCount) {
            foreach ($accounts as $account) {
                $this->info("Syncing negotiations for user_id={$account->user_id} (account_id={$account->id})");

                // Try refresh if expired
                if ($account->expires_at && $account->expires_at->isPast()) {
                    try {
                        $acctRepo->refreshToken($account);
                    } catch (\Throwable $e) {
                        Log::warning('HH token refresh failed (pre-fetch)', ['account_id' => $account->id, 'error' => $e->getMessage()]);
                    }
                }

                $page = 0;
                $pagesDone = 0;
                do {
                    $resp = $hh->listNegotiations($page, $perPage, $account);
                    if (!($resp['success'] ?? false)) {
                        $this->warn("Negotiations fetch failed for account {$account->id}: " . ($resp['message'] ?? 'unknown'));
                        break;
                    }
                    $data = $resp['data'] ?? [];
                    $items = $data['items'] ?? $data['negotiations'] ?? [];
                    if (empty($items)) {
                        break;
                    }
                    foreach ($items as $item) {
                        $scannedCount++;
                        $vacancyExternalId = Arr::get($item, 'vacancy.id');
                        $resumeId = (string) (Arr::get($item, 'resume.id') ?? '');
                        $stateId = Arr::get($item, 'state.id') ?? Arr::get($item, 'state.name') ?? Arr::get($item, 'status');
                        if (!$vacancyExternalId || !$stateId) {
                            continue;
                        }

                        $vacancy = Vacancy::where('external_id', $vacancyExternalId)->first();
                        if (!$vacancy) {
                            continue;
                        }

                        $app = Application::where('user_id', $account->user_id)
                            ->where('vacancy_id', $vacancy->id)
                            ->first();
                        if (!$app) {
                            continue;
                        }

                        if ($resumeId !== '' && $app->hh_resume_id && (string) $app->hh_resume_id !== $resumeId) {
                            continue;
                        }
                        Log::info('Matching negotiation found', [
                            'application_id' => $app->id,
                            'vacancy_id' => $vacancy->id,
                            'vacancy_external_id' => $vacancyExternalId,
                            'current_hh_status' => $app->hh_status,
                            'new_hh_status' => $stateId,
                        ]);
                        $this->line(" - Application ID {$app->id}: HH status {$app->hh_status} -> {$stateId}");
                        if ($app->hh_status !== $stateId) {
                            $app->update(['hh_status' => $stateId, 'status' => $stateId]);
                            $updatedCount++;

                            $triggerStates = ['interview', 'interview_scheduled', 'invitation', 'offer', 'hired','invited', 'assessments', 'assessment', 'test'];

                            if (in_array($stateId, $triggerStates, true)) {
                                if ($vacancy->source === config('interviews.source_filter', 'hh')) {
                                    if (!$app->interview_job_dispatched_at) {
                                        \Modules\Interviews\Jobs\HandleInterviewApplication::dispatch($app->id);
                                        $app->update(['interview_job_dispatched_at' => now()]);
                                    }
                                }
                            }
                        }

                        // Ensure discard interview presence for decline/discard states (idempotent)
                        $discardStates = (array) config('interviews.discard_statuses', ['discard']);
                        $discardPatterns = (array) config('interviews.discard_patterns', []);
                        $isDiscard = in_array($stateId, $discardStates, true);
                        if (!$isDiscard && !empty($discardPatterns)) {
                            foreach ($discardPatterns as $pattern) {
                                if ($pattern !== '' && stripos((string) $stateId, (string) $pattern) !== false) {
                                    $isDiscard = true;
                                    break;
                                }
                            }
                        }
                        if ($isDiscard) {
                            try {
                                app(\Modules\Interviews\Services\InterviewService::class)
                                    ->ensureDiscardForApplication($app);
                            } catch (\Throwable $e) {
                                Log::warning('Ensure discard interview failed', [
                                    'application_id' => $app->id,
                                    'state' => $stateId,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    }

                    $page++;
                    $pagesDone++;
                } while ($pagesDone < max(1, $maxPages));
            }
        });

        $this->info("Negotiations scanned: {$scannedCount}, applications updated: {$updatedCount}");
        Log::info('HH negotiations sync done', ['scanned' => $scannedCount, 'updated' => $updatedCount]);
        return self::SUCCESS;
    }
}
