<?php

namespace Modules\TelegramBot\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Modules\TelegramBot\Services\TelegramBotService;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramBotController extends Controller
{
    protected $botService;

    public function __construct(TelegramBotService $botService)
    {
        $this->botService = $botService;
    }

    public function handleWebhook(Request $request)
    {
        $update = Telegram::getWebhookUpdate();
        Log::info("Webhook received", $update->toArray());

        if (isset($update['message'])) {
            $message = $update['message'];
            $chatId  = $message['chat']['id'];
            $text    = $message['text'] ?? null;

            Log::info("Message received => chatId: {$chatId}, text: {$text}");

            if ($text === '/start') {
                $firstName = $message['from']['first_name'] ?? '';
                $lastName  = $message['from']['last_name'] ?? '';

                $this->botService->sendWelcomeMessage($chatId, $firstName, $lastName);
                $this->botService->sendLanguageSelection($chatId);
            }

            if (in_array($text, ['🇺🇿 O\'zbek', '🇷🇺 Русский', '🇬🇧 English'])) {
                $this->botService->handleLanguageSelection($chatId, $text);
            }

            if ($this->botService->isBackButton($chatId, $text)) {
                $this->botService->sendLanguageSelection($chatId);
            }
        }

        return response('OK', 200);
    }

}
