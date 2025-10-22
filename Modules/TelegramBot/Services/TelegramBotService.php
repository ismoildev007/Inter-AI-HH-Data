<?php

namespace Modules\TelegramBot\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramBotService
{
    public function sendWelcomeMessage($chatId)
    {
        $text = "Assalomu alaykum! inter-AI vakansiyalari botiga xush kelibsiz!";
        Log::info("sendWelcomeMessage => chatId: {$chatId}, text: {$text}");

        Telegram::bot('mybot')->sendMessage([
            'chat_id' => $chatId,
            'text'    => $text,
        ]);
    }


    public function sendLanguageSelection($chatId)
    {
        $text = "Iltimos, tilni tanlang / Пожалуйста, выберите язык / Please select a language:";
        Log::info("sendLanguageSelection => chatId: {$chatId}");

        $keyboard = Keyboard::make()
            ->setResizeKeyboard(true)
            ->row([
                Keyboard::button('🇺🇿 O\'zbek'),
                Keyboard::button('🇷🇺 Русский'),
                Keyboard::button('🇬🇧 English'),
            ]);

        Telegram::bot('mybot')->sendMessage([
            'chat_id'      => $chatId,
            'text'         => $text,
            'reply_markup' => $keyboard,
        ]);
    }

    public function handleLanguageSelection($chatId, $language)
    {
        $lastMessageId = Cache::get("last_message_{$chatId}");
        if ($lastMessageId) {
            try {
                Telegram::bot('mybot')->deleteMessage([
                    'chat_id'    => $chatId,
                    'message_id' => $lastMessageId,
                ]);
            } catch (\Exception $e) {
                Log::warning("Xabar o‘chirishda xatolik: " . $e->getMessage());
            }
        }

        Cache::put("lang_{$chatId}", $language, now()->addHours(24));
        Log::info("handleLanguageSelection => chatId: {$chatId}, lang: {$language}");

        $texts = [
            '🇺🇿 O\'zbek' => 'Platformamizdan foydalanish uchun "Dasturga kirish" tugmasini bosing!',
            '🇷🇺 Русский' => 'Чтобы использовать нашу платформу, нажмите кнопку «Войти в программу»!',
            '🇬🇧 English' => 'To use our platform, please click the "Sign in" button!',
        ];
        $text = $texts[$language] ?? $texts['🇺🇿 O\'zbek'];

        $langCodeMap = [
            '🇺🇿 O\'zbek' => 'uz',
            '🇷🇺 Русский' => 'ru',
            '🇬🇧 English' => 'en',
        ];
        $langCode = $langCodeMap[$language] ?? 'uz';

        $user = User::where('chat_id', $chatId)->first();

        if ($user) {
            $user->tokens()->delete();
            $token = $user->createToken('api_token', ['*'], now()->addDays(30))->plainTextToken;
            $url = "https://vacancies.inter-ai.uz/#?locale={$langCode}&token={$token}&chat_id={$chatId}";
        } else {
            $url = "https://vacancies.inter-ai.uz/#?locale={$langCode}&chat_id={$chatId}";
        }

        $inlineKeyboard = Keyboard::make()
            ->inline()
            ->row([
                Keyboard::inlineButton([
                    'text'    => $this->getViewVacanciesText($language),
                    'web_app' => ['url' => $url],
                ]),
            ]);

        $backKeyboard = Keyboard::make()
            ->setResizeKeyboard(true)
            ->row([Keyboard::button($this->getBackButtonText($language))]);

        try {
            $response = Telegram::bot('mybot')->sendMessage([
                'chat_id'      => $chatId,
                'text'         => $text,
                'reply_markup' => $inlineKeyboard,
            ]);

            Cache::put("last_message_{$chatId}", $response->getMessageId(), now()->addHours(24));

            $backInstructionTexts = [
                '🇺🇿 O\'zbek' => "Agar tilni o‘zgartirmoqchi bo‘lsangiz, ⬅️ Orqaga tugmasini bosing.",
                '🇷🇺 Русский' => "Если хотите изменить язык, нажмите кнопку ⬅️ Назад.",
                '🇬🇧 English' => "If you want to change the language, press ⬅️ Back.",
            ];
            $backInstruction = $backInstructionTexts[$language] ?? $backInstructionTexts['🇺🇿 O\'zbek'];

            Telegram::bot('mybot')->sendMessage([
                'chat_id'      => $chatId,
                'text'         => $backInstruction,
                'reply_markup' => $backKeyboard,
            ]);

            Log::info("handleLanguageSelection => messages sent successfully!");
        } catch (\Exception $e) {
            Log::error("handleLanguageSelection ERROR: " . $e->getMessage());
        }
    }



    public function getViewRegisterText($language)
    {
        $texts = [
            '🇺🇿 O\'zbek' => 'Ro\'yxatdan o\'tish',
            '🇷🇺 Русский' => 'Зарегистрироваться',
            '🇬🇧 English' => 'Sign up',
        ];
        return $texts[$language] ?? 'Ro\'yxatdan o\'tish';
    }
    public function getViewVacanciesText($language)
    {
        $texts = [
            '🇺🇿 O\'zbek' => 'Dasturga Kirish',
            '🇷🇺 Русский' => 'Войти в программу',
            '🇬🇧 English' => 'Sign in',
        ];
        return $texts[$language] ?? 'Kirish';
    }

    public function getBackButtonText($language)
    {
        $texts = [
            '🇺🇿 O\'zbek' => '⬅️ Orqaga',
            '🇷🇺 Русский' => '⬅️ Назад',
            '🇬🇧 English' => '⬅️ Back',
        ];
        return $texts[$language] ?? '⬅️ Orqaga';
    }

    public function isBackButton($chatId, $text)
    {
        $lang = Cache::get("lang_{$chatId}", '🇺🇿 O\'zbek');
        Log::info("isBackButton => chatId: {$chatId}, lang: {$lang}, text: {$text}");
        return $text === $this->getBackButtonText($lang);
    }
}
