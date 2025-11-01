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

        $users = User::get();
        $this->line('Found ' . $users->count() . ' users with resumes and chat IDs.');

        if ($users->isEmpty()) {
            $this->warn('No users found with valid resumes and chat IDs.');
            return;
        }
        foreach ($users as $user) {
            $this->line("👤 Checking matches for user: {$user->first_name}");

            $totalNewMatches = 0;
            $localList = [];
            $hhList = [];
            $seenVacancyIds = [];

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

                    // Build per-source short lists for Telegram message (max 10 + 10)
                    foreach ($newMatches as $match) {
                        $vac = $match->vacancy;
                        if (!$vac) { continue; }
                        if (in_array($vac->id, $seenVacancyIds, true)) { continue; }
                        $seenVacancyIds[] = $vac->id;

                        $title = $vac->title ?? '—';
                        $title = $this->cleanTitle($title);

                        if ($vac->source === 'telegram' && count($localList) < 10) {
                            $localList[] = $title;
                        } elseif ($vac->source === 'hh' && count($hhList) < 10) {
                            $hhList[] = $title;
                        }
                    }

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

                    // Compose optional detailed lists (titles) per source
                    $sections = [];
                    if (!empty($localList)) {
                        $header = $user->language === 'ru' ? 'Telegram вакансии' : ($user->language === 'uz' ? 'Telegram vakansiyalar' : 'Telegram vacancies');
                        $lines = [];
                        foreach ($localList as $i => $t) { $lines[] = ($i + 1) . '. ' . $t; }
                        $sections[] = $header . ":\n" . implode("\n", $lines);
                    }
                    if (!empty($hhList)) {
                        $header = $user->language === 'ru' ? 'HH вакансии' : ($user->language === 'uz' ? 'HH vakansiyalar' : 'HH vacancies');
                        $lines = [];
                        foreach ($hhList as $i => $t) { $lines[] = ($i + 1) . '. ' . $t; }
                        $sections[] = $header . ":\n" . implode("\n", $lines);
                    }

                    if (!empty($sections)) {
                        $message .= "\n\n" . implode("\n\n", $sections);
                    }

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

    private function cleanTitle(string $text): string
    {
        $text = strip_tags($text);
        $text = preg_replace('/\s+/u', ' ', $text);
        // Remove most Markdown control chars to avoid formatting issues
        $text = str_replace(["*", "_", "`"], '', $text);
        $text = trim($text);
        return mb_strlen($text) > 70 ? (mb_substr($text, 0, 70) . '…') : $text;
    }
}
