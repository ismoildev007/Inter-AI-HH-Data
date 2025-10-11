<?php

namespace App\Console\Commands;

use App\Models\MatchResult;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Vacancies\Services\VacancyMatchingService;
use Telegram\Bot\Api;

class SendNotificationCommand extends Command
{

    protected $signature = 'app:send-notification-command';
    protected $description = 'Command description';

    protected VacancyMatchingService $matchingService;

    public function __construct(VacancyMatchingService $matchingService)
    {
        parent::__construct();
        $this->matchingService = $matchingService;
    }
    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('🚀 Matching and notification started.');

        $token = '8086335636:AAGGAWtnPfbDGUviunLMwk7S7y2yNPUkl4Q';
        $telegram = new Api($token);

        $users = User::whereNotNull('chat_id')
            ->whereHas('resumes', function ($q) {
                $q->whereNotNull('parsed_text');
            })
            ->with('resumes')
            ->get();

        if ($users->isEmpty()) {
            $this->warn('No users found with valid resumes and chat IDs.');
            return;
        }
        foreach ($users as $user) {
            $this->line("👤 Checking matches for user: {$user->email}");

            $totalNewMatches = 0;

            foreach ($user->resumes as $resume) {
                $this->line("   🧠 Matching resume #{$resume->id}: {$resume->title}");

                try {
                    $savedData = $this->matchingService->matchResume($resume, $resume->title ?? 'developer');
                } catch (\Throwable $e) {
                    Log::error("❌ Error matching resume {$resume->id}: " . $e->getMessage());
                    continue;
                }

                $newMatches = MatchResult::where('resume_id', $resume->id)
                    ->whereNull('notified_at')
                    ->with('vacancy')
                    ->get();

                if ($newMatches->isNotEmpty()) {
                    $totalNewMatches += $newMatches->count();

                    MatchResult::whereIn('id', $newMatches->pluck('id'))
                        ->update(['notified_at' => now()]);
                }
            }
            if ($totalNewMatches > 0) {
                try {
                    $message = "💼 *Good news!*\n\nWe found *{$totalNewMatches}* new matching vacancies for your resume.\n\n👉 Check your account to view them.";
                    $telegram->sendMessage([
                        'chat_id' => $user->chat_id,
                        'text' => $message,
                        'parse_mode' => 'Markdown',
                    ]);

                    $this->info("✅ Sent message to {$user->email} ({$totalNewMatches} matches)");
                    Log::info("✅ Notification sent to user {$user->id}");
                } catch (\Throwable $e) {
                    Log::error("❌ Telegram send failed for user {$user->id}: " . $e->getMessage());
                }
            } else {
                $this->line("ℹ️ No new matches for {$user->email}");
            }
        }
        Log::info('✅ Matching and notifications completed.');

    }
}
