<?php

namespace App\Console\Commands;

use App\Models\MatchResult;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Vacancies\Services\VacancyMatchingService;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;

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

        $users = User::get();
        $this->line('Found ' . $users->count() . ' users with resumes and chat IDs.');

        if ($users->isEmpty()) {
            $this->warn('No users found with valid resumes and chat IDs.');
            return;
        }
        foreach ($users as $user) {
            $this->line("👤 Checking matches for user: {$user->first_name}");

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
                $this->line("      🔍 Found {$newMatches->count()} new matches for resume #{$resume->id}");

                if ($newMatches->isNotEmpty()) {
                    $totalNewMatches += $newMatches->count();
                    $this->info("      ✅ New matches for resume #{$resume->id}: {$newMatches->count()}");

                    MatchResult::whereIn('id', $newMatches->pluck('id'))
                        ->update(['notified_at' => now()]);
                    $this->info("      🕒 Updated notified_at for resume #{$resume->id}");
                }
            }
            $this->line("   🎯 Total new matches for user {$user->first_name}: {$totalNewMatches}");
            if ($totalNewMatches > 0) {
                try {
                    $langCode = $user->language ?? 'ru';

                    if ($user->language === 'uz') {
                        $message = "Sun’iy intellekt siz uchun aynan mos bo‘lgan ish o‘rnini topdi! 🚀\n\nImkonni qo‘ldan boy bermang — batafsil ma’lumotni ilovada ko’rishingiz mumkin👇";
                        $buttonText = "Akkauntga kirish";
                    } elseif ($user->language === 'ru') {
                        $message = "Наш ИИ нашёл для вас вакансию, которая идеально подходит! 🚀 \n\nНе упустите шанс — посмотрите подробности прямо сейчас в приложении 👇";
                        $buttonText = "Войти в аккаунт";
                    } else {
                        $message = "Our AI has found a job that perfectly matches your profile! 🚀\n\nDon’t miss this opportunity — check the details in the app right now 👇";
                        $buttonText = "Enter Account";
                    }

                    $token = $user->createToken('api_token', ['*'], now()->addYears(22))->plainTextToken;
                    $webAppUrl = "https://vacancies.inter-ai.uz/#?token={$token}&chat_id={$user->chat_id}&locale={$langCode}";
                    
                    $inlineKeyboard = Keyboard::make()
                        ->inline()
                        ->row([
                            Keyboard::inlineButton([
                                'text'    => $buttonText,
                                'web_app' => ['url' => $webAppUrl],
                            ]),
                        ]);

                    try {
                        $telegram->sendMessage([
                            'chat_id'      => $user->chat_id,
                            'text'         => $message,
                            'parse_mode'   => 'Markdown',
                            'reply_markup' => $inlineKeyboard,
                        ]);

                        Log::info("✅ Dashboard button sent to user {$user->id}");
                    } catch (\Throwable $e) {
                        Log::error("❌ Telegram send failed for user {$user->id}: " . $e->getMessage());
                    }
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
