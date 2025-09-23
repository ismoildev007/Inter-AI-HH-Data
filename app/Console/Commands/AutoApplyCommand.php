<?php

namespace App\Console\Commands;

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
            ->where('auto_apply_limit', '>', 0)
            ->with(['user.credit', 'user.hhAccount', 'user.resumes.matchResults.vacancy'])
            ->get();

        $hhService = new HhApiService();

        foreach ($settings as $setting) {
            $user = $setting->user;
            Log::info([
                'user credit' => $user->credit,
                'balance' => $user->credit->balance
            ]);
            if (!$user->credit || $user->credit->balance <= 0) {
                continue;
            }

            if (!$user->hhAccount) {
                continue;
            }

            $matches = $user->resumes->flatMap->matchResults->where('score_percent', '>=', 70);
            Log::info(['matches' => $matches]);
            foreach ($matches as $match) {
                $exists = Application::where('user_id', $user->id)
                    ->where('vacancy_id', $match->vacancy_id)
                    ->exists();
                if ($exists) continue;

                DB::transaction(function () use ($user, $setting, $match, $hhService) {
                    $application = Application::create([
                        'user_id'     => $user->id,
                        'vacancy_id'  => $match->vacancy_id,
                        'resume_id'   => $match->resume_id,
                        'hh_resume_id'=> $setting->resume_id,
                        'status'      => 'pending',
                        'match_score' => $match->score_percent,
                        'submitted_at'=> now(),
                    ]);

                    try {
                        $hhService->apply($application);

                        $setting->decrement('auto_apply_limit');
                        $user->credit->decrement('balance');

                        $application->update(['status' => 'response']);
                    } catch (\Exception $e) {
                        $application->update(['status' => 'failed']);
                        Log::error("Auto apply failed", [
                            'user_id' => $user->id,
                            'vacancy_id' => $match->vacancy_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                });
            }
        }
    }
}
