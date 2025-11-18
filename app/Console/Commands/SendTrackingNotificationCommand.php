<?php

namespace App\Console\Commands;

use App\Models\CareerTrackingPdf;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;

class SendTrackingNotificationCommand extends Command
{
    protected $signature = 'app:send-tracking-notification {user_id?}';
    protected $description = 'Send tracking page button to users (based on CareerTrackingPdf)';

    public function handle()
    {
        Log::info("📄 Career Tracking Notification Started");

        $telegram = new Api('8086335636:AAGGAWtnPfbDGUviunLMwk7S7y2yNPUkl4Q');

        // ↪ If specific user ID provided
        if ($this->argument('user_id')) {
            $users = User::where('id', $this->argument('user_id'))->get();
        } else {
            $users = User::whereNotNull('chat_id')->get();
        }

        if ($users->isEmpty()) {
            $this->warn("⚠️ No users found with chat_id");
            return;
        }

        foreach ($users as $user) {
            $this->line("👤 User: {$user->first_name} ({$user->id})");

            foreach ($user->resumes as $resume) {

                $pdfRecord = $resume->careerTrackingPdf;

                if (!$pdfRecord) {
                    $this->warn("⛔ Resume #{$resume->id} has no tracking PDF/JSON, skipping...");
                    continue;
                }

                $this->info("📌 Resume #{$resume->id} has CareerTracking data");

                // 🟢 Token generation (Sanctum)
                // $user->tokens()->delete();
                $token = $user->createToken('api_token', ['*'], now()->addDays(30))->plainTextToken;
                Log::info("🔑 Created tracking token for user {$user->id}");

                // 🌍 Language
                $lang = $user->language ?? 'uz';

                // 🔗 Tracking page URL
                $trackingUrl = "https://vacancies.inter-ai.uz/#/career?"
                    . "resume_id={$resume->id}"
                    . "&token={$token}"
                    . "&locale={$lang}";

                // 🌐 Telegram message text
                if ($lang === 'uz') {
                    $message = "Siz uchun ishlab chiqilgan karyera tahlilingiz tayyor! 📊\n\n"
                        . "Uni hoziroq oching va to‘liq hisobotni ko‘ring 👇";
                    $button = "Karyera tahlilini ko’rish";
                }
                elseif ($lang === 'ru') {
                    $message = "Ваш персональный карьерный анализ готов! 📊\n\n"
                        . "Откройте его прямо сейчас и посмотрите полный отчёт 👇";
                    $button = "Посмотреть карьерный анализ";
                }
                else {
                    $message = "Your personalized career analysis is ready! 📊\n\n"
                        . "Open it now and view your full report 👇";
                    $button = "View Career Analysis";
                }

                // 🟦 Telegram button
                $inlineKeyboard = Keyboard::make()
                    ->inline()
                    ->row([
                        Keyboard::inlineButton([
                            'text'    => $button,
                            'web_app' => ['url' => $trackingUrl],
                        ]),
                    ]);

                // 📩 Send message
                try {
                    $telegram->sendMessage([
                        'chat_id'      => $user->chat_id,
                        'text'         => $message,
                        'parse_mode'   => 'Markdown',
                        'reply_markup' => $inlineKeyboard,
                    ]);

                    $this->info("✅ Tracking button sent to user {$user->id}");
                    Log::info("📨 Tracking message sent", [
                        'user_id' => $user->id,
                        'resume_id' => $resume->id,
                    ]);

                } catch (\Throwable $e) {
                    Log::error("❌ Telegram error for user {$user->id}: " . $e->getMessage());
                }
            }
        }

        Log::info("🎉 Career Tracking Notification Completed");
        $this->info("🎉 Completed.");
    }
}
