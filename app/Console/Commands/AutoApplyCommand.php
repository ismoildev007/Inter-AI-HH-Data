<?php

namespace App\Console\Commands;

use App\Jobs\AutoApplyJob;
use App\Models\Application;
use App\Models\UserSetting;
use App\Services\HhApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoApplyCommand extends Command
{

    protected $signature = 'autoapply:start';
    protected $description = 'Automatically apply to HH vacancies for eligible users';

    public function handle()
    {
        Log::info('auto apply');
        $settings = UserSetting::where('auto_apply_enabled', true)
            ->with(['user.credit', 'user.hhAccount', 'user.resumes.matchResults.vacancy'])
            ->get()
            ->filter(function ($setting) {
                return $setting->auto_apply_count < $setting->auto_apply_limit;
            });


        $hhService = new HhApiService();

        foreach ($settings as $setting) {
            $user = $setting->user;
            $balance = optional($user->credit)->balance;

            if ($balance === null || $balance < 0) {
                continue;
            }

            if (!$user->hhAccount) {
                continue;
            }

            if (is_null($setting->resume_id)) {
                Log::warning("User {$user->id} skipped — missing resume_id");
                continue;
            }

            $remaining = $setting->auto_apply_limit - $setting->auto_apply_count;
            if ($remaining <= 0) {
                continue; 
            }

            $matches = $user->resumes->flatMap->matchResults->where('score_percent', '>=', 70);
            foreach ($matches as $match) {
                if ($remaining <= 0) {
                    break; 
                }
                $exists = Application::where('user_id', $user->id)
                    ->where('vacancy_id', $match->vacancy_id)
                    ->exists();
                if ($exists) continue;

                DB::transaction(function () use ($user, $setting, $match, $hhService) {
                    $application = Application::create([
                        'user_id'     => $user->id,
                        'vacancy_id'  => $match->vacancy_id,
                        'resume_id'   => $match->resume_id,
                        'hh_resume_id' => $setting->resume_id,
                        'status'      => 'pending',
                        'match_score' => $match->score_percent,
                        'submitted_at' => now(),
                    ]);

                    AutoApplyJob::dispatch($application)
                        ->onQueue('autoapply');
                });

                $remaining--; 
            }
        }
    }
}
