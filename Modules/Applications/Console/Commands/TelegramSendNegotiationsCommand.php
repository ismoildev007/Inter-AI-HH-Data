<?php

namespace Modules\Applications\Console\Commands;

use App\Models\Application;
use App\Models\HhAccount;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Modules\Users\Repositories\HhAccountRepositoryInterface;
use Modules\Vacancies\Interfaces\HHVacancyInterface;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramSendNegotiationsCommand extends Command
{
    protected $signature = 'hh:telegram-send-negotiations';
    protected $description = 'Send HH negotiations and update applications.hh_status for users with HH accounts';

    public function handle()
    {
        $this->info('Starting HH negotiations check...');

        $rejectionStates = ['discard', 'rejected', 'discarded', 'declined', 'refusal'];
        $offerStates = ['interview', 'interview_scheduled', 'invitation', 'offer', 'hired', 'invited', 'assessments', 'assessment', 'test'];

        $users = User::whereNotNull('chat_id')
            ->get();

        foreach ($users as $user) {
            $this->info("Processing user: {$user->id}");

            $applications = Application::where('user_id', $user->id)
                ->where('status', '!=', ['responses', 'already_applied'])
                ->where('notify_status', false)
                ->with('vacancy')
                ->get();

            if ($applications->isEmpty()) {
                $this->info("No new applications for user {$user->id}");
                continue;
            }

            $rejections = [];
            $offers = [];

            foreach ($applications as $application) {
                $stateId = strtolower($application->status);
                $vacancyTitle = $application->vacancy->title ?? 'Noma\'lum vakansiya';

                if (in_array($stateId, $rejectionStates, true)) {
                    $rejections[] = $vacancyTitle;
                } elseif (in_array($stateId, $offerStates, true)) {
                    $offers[] = $vacancyTitle;
                }
            }

            if (empty($rejections) && empty($offers)) {
                continue;
            }

            $lang = $user->language ?? 'uz';
            $messageText = $this->buildMessage($rejections, $offers, $lang);

            $user->tokens()->delete();
            $token = $user->createToken('api_token', ['*'], now()->addDays(30))->plainTextToken;
            $webAppUrl = "https://vacancies.inter-ai.uz/#?token={$token}&chat_id={$user->chat_id}";

            $btnText = match ($lang) {
                'uz' => "Dasturga kirish",
                'ru' => "Войти в программу",
                default => "Open App",
            };

            $inlineKeyboard = Keyboard::make()
                ->inline()
                ->row([
                    Keyboard::inlineButton([
                        'text'    => $btnText,
                        'web_app' => ['url' => $webAppUrl],
                    ]),
                ]);

            try {
                Telegram::bot('mybot')->sendMessage([
                    'chat_id'      => $user->chat_id,
                    'text'         => $messageText,
                    'parse_mode'   => 'Markdown',
                    'reply_markup' => $inlineKeyboard,
                ]);

                Application::where('user_id', $user->id)
                    ->whereNotNull('status')
                    ->where('notify_status', false)
                    ->update(['notify_status' => true]);

                $this->info("Message sent to user {$user->id}");
            } catch (\Exception $e) {
                $this->error("Failed to send message to user {$user->id}: {$e->getMessage()}");
            }
        }

        $this->info('HH negotiations check completed!');
    }

    /**
     * Xabar matnini yaratish
     */
    private function buildMessage(array $rejections, array $offers, string $lang): string
    {
        // Bosh sarlavha
        $title = match ($lang) {
            'uz' => "📢 *Siz topshirgan vakansiyalar bo'yicha yangilanishlar keldi*\n\n",
            'ru' => "📢 *Получены обновления по вашим вакансиям*\n\n",
            default => "📢 *Updates received for your applications*\n\n",
        };

        $message = $title;

        // Rad javoblar bo'limi
        if (!empty($rejections)) {
            $header = match ($lang) {
                'uz' => "❌ *Rad javoblar:*\n\n",
                'ru' => "❌ *Отказы:*\n\n",
                default => "❌ *Rejections:*\n\n",
            };

            $message .= $header;
            foreach ($rejections as $index => $vacancy) {
                $message .= ($index + 1) . ". {$vacancy}\n";
            }
            $message .= "\n";
        }

        // Takliflar bo'limi
        if (!empty($offers)) {
            $header = match ($lang) {
                'uz' => "✅ *Takliflar:*\n\n",
                'ru' => "✅ *Предложения:*\n\n",
                default => "✅ *Offers:*\n\n",
            };

            $message .= $header;
            foreach ($offers as $index => $vacancy) {
                $message .= ($index + 1) . ". {$vacancy}\n";
            }
            $message .= "\n";
        }

        // Oxirgi qism
        $footer = match ($lang) {
            'uz' => "Batafsil ma'lumotni ilovada ko'rishingiz mumkin👇",
            'ru' => "Посмотреть детали в приложении👇",
            default => "Open the app for details👇",
        };

        $message .= $footer;

        return $message;
    }
}
