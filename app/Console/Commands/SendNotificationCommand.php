<?php

namespace App\Console\Commands;

use App\Models\MatchResult;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Vacancies\Services\NotificationMatchingService;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;

class SendNotificationCommand extends Command
{

    protected $signature = 'app:send-notification-command';
    protected $description = 'Command description';

    protected NotificationMatchingService $matchingService;

    public function __construct(NotificationMatchingService $matchingService)
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
                        $message = "Sun’iy intellekt siz uchun aynan mos bo‘lgan *{$totalNewMatches}* ta ish o‘rnini topdi! 🚀\n\nImkonni qo‘ldan boy bermang — batafsil ma’lumotni ilovada ko’rishingiz mumkin👇";
                        $buttonText = "Dasturga Kirish";
                    } elseif ($user->language === 'ru') {
                        $message = "Наш ИИ нашёл для вас *{$totalNewMatches}* подходящих вакансий! 🚀\n\nНе упустите шанс — посмотрите подробности прямо сейчас в приложении 👇";
                        $buttonText = "Войти в программу";
                    } else {
                        $message = "Our AI has found *{$totalNewMatches}* job positions that perfectly match your profile! 🚀\n\nDon’t miss this opportunity — check the details in the app right now 👇";
                        $buttonText = "Sign in";
                    }
                    $user->tokens()->delete();

                    $token = $user->createToken('api_token', ['*'], now()->addDays(30))->plainTextToken;
                    $webAppUrl = "https://vacancies.inter-ai.uz/#?chat_id={$user->chat_id}&token={$token}&locale={$langCode}";

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

                try {
                    $langCode = $user->language ?? 'ru';
                    $vacancyCount = Vacancy::count();

                    if ($user->language === 'uz') {
                        $message = "Siz uchun hozirda *{$vacancyCount}* ta faol ish o‘rni mavjud 💼\n\nIlovaga kirib, sizga mos yangi takliflarni kuzatib boring — imkonni boy bermang! 🚀👇";
                        $buttonText = "Dasturga Kirish";
                    } elseif ($user->language === 'ru') {
                        $message = "В системе сейчас доступно *{$vacancyCount}* активных вакансий 💼\n\nОткройте приложение и следите за новыми предложениями — не упустите свой шанс! 🚀👇";
                        $buttonText = "Войти в программу";
                    } else {
                        $message = "There are currently *{$vacancyCount}* active job openings available 💼\n\nOpen the app and stay tuned for new opportunities that match your profile! 🚀👇";
                        $buttonText = "Sign in";
                    }


                    $user->tokens()->delete();
                    $token = $user->createToken('api_token', ['*'], now()->addDays(30))->plainTextToken;
                    $webAppUrl = "https://vacancies.inter-ai.uz/#?chat_id={$user->chat_id}&token={$token}&locale={$langCode}";

                    $inlineKeyboard = Keyboard::make()
                        ->inline()
                        ->row([
                            Keyboard::inlineButton([
                                'text'    => $buttonText,
                                'web_app' => ['url' => $webAppUrl],
                            ]),
                        ]);

                    $telegram->sendMessage([
                        'chat_id'      => $user->chat_id,
                        'text'         => $message,
                        'parse_mode'   => 'Markdown',
                        'reply_markup' => $inlineKeyboard,
                    ]);

                    Log::info("📩 No-match info sent to user {$user->id} ({$vacancyCount} vacancies in system)");
                } catch (\Throwable $e) {
                    Log::error("❌ Telegram send (no matches) failed for user {$user->id}: " . $e->getMessage());
                }
            }
        }
        Log::info('✅ Matching and notifications completed.');
    }

    // private function cleanTitle(string $text): string
    // {
    //     $text = strip_tags($text);
    //     $text = preg_replace('/\s+/u', ' ', $text);
    //     // Remove most Markdown control chars to avoid formatting issues
    //     $text = str_replace(["*", "_", "`"], '', $text);
    //     $text = trim($text);
    //     return mb_strlen($text) > 70 ? (mb_substr($text, 0, 70) . '…') : $text;
    // }
}
