<?php

namespace App\Console\Commands;

use App\Models\MatchResult;
use App\Models\User;
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

// ✅ faqat bitta test userni olamiz
        $user = User::where('chat_id', 1770556788)->first();

        if (!$user) {
            $this->warn('⚠️ Test user topilmadi.');
            return;
        }

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

                MatchResult::whereIn('id', $newMatches->pluck('id'))
                    ->update(['notified_at' => now()]);
            }
        }

        if ($totalNewMatches > 0) {
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

            // token yaratish
            $user->tokens()->delete();
            $token = $user->createToken('api_token', ['*'], now()->addDays(30))->plainTextToken;

            // test uchun shu userning chat_id ishlatamiz
            $webAppUrl = "https://vacancies.inter-ai.uz/#?chat_id={$user->chat_id}&token={$token}&locale={$langCode}";

            $inlineKeyboard = Keyboard::make()
                ->inline()
                ->row([
                    Keyboard::inlineButton([
                        'text'    => $buttonText,
                        'web_app' => ['url' => $webAppUrl],
                    ]),
                ]);

            // ✅ faqat shu userga yuboradi
            $telegram->sendMessage([
                'chat_id'      => $user->chat_id,
                'text'         => $message,
                'parse_mode'   => 'Markdown',
                'reply_markup' => $inlineKeyboard,
            ]);

            Log::info("✅ Test notification sent to user {$user->id}");
        } else {
            $this->line("ℹ️ No new matches for {$user->email}");
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
