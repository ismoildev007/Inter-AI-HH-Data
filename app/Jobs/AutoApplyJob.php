<?php

namespace App\Jobs;

use App\Models\Application;
use App\Models\CreditTransaction;
use App\Services\HhApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoApplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Application $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * Execute the job.
     */
    public function handle(HhApiService $hhService): void
    {
       // Log::info("Executing AutoApplyJob for Application ID: {$this->application->id}");
        $application = $this->application;
        $user = $application->user;
        $setting = $user->settings;

        try {
            DB::transaction(function () use ($application, $user, $setting, $hhService) {
                $vacancy = $application->vacancy;
                $hhService->apply($application);

                // $setting->increment('auto_apply_count');
                // $user->credit->decrement('balance');
                $newBalance = $user->credit->fresh()->balance;

                CreditTransaction::create([
                    'user_id' => $user->id,
                    'type'    => 'spend',
                    'amount'  => -1,
                    'balance_after' => $newBalance,
                    'related_application_id' => $application->id,
                ]);

                $application->update(['status' => 'response']);
            });
        } catch (\Throwable $e) {
            try {
                $application->status = 'already_applied';
                $application->save();
            } catch (\Throwable $ex) {
                Log::error("Failed to delete application after error", [
                    'application_id' => $application->id,
                    'error' => $ex->getMessage(),
                ]);
            }

            Log::error("Auto apply job failed", [
                'application_id' => $application->id,
                'user_id'        => $application->user_id,
                'error'          => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }
}
