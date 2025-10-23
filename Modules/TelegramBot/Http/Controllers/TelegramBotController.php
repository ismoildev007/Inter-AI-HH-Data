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
                $this->botService->sendWelcomeMessage($chatId);
                $this->botService->sendLanguageSelection($chatId);
            }

            if ($this->botService->isBackButton($chatId, $text)) {
                $this->botService->sendLanguageSelection($chatId);
                return;
            }

            if (in_array($text, ['🇺🇿 O\'zbek', '🇷🇺 Русский', '🇬🇧 English'])) {
                $this->botService->handleLanguageSelection($chatId, $text);
            }


        }

        return response('OK', 200);
    }

    public function handleUpdate()
    {
        $update = Telegram::bot('mybot')->getWebhookUpdate();
        $message = $update->getMessage();
        $chatId = $message->chat->id;
        $text = $message->text;

        $service = app(\Modules\TelegramBot\Services\TelegramBotService::class);

        // 🔙 If user clicks "Back"
        if ($service->isBackButton($chatId, $text)) {
            $service->sendLanguageSelection($chatId);
            return;
        }

        // 🌐 If user selects a language
        if (in_array($text, ['🇺🇿 O\'zbek', '🇷🇺 Русский', '🇬🇧 English'])) {
            $service->handleLanguageSelection($chatId, $text);
            return;
        }

        // 👋 Otherwise, default welcome
        $service->sendWelcomeMessage($chatId);
    }
}
