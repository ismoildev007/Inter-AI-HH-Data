<?php

namespace Modules\TelegramBot\Services;

use App\Models\User;
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

        // ✅ Inline keyboard (instead of normal reply buttons)
        $inlineKeyboard = Keyboard::make()
            ->inline()
            ->row(
                Keyboard::inlineButton(['text' => '🇺🇿 O\'zbek',  'callback_data' => 'lang_uz']),
                Keyboard::inlineButton(['text' => '🇷🇺 Русский', 'callback_data' => 'lang_ru']),
                Keyboard::inlineButton(['text' => '🇬🇧 English', 'callback_data' => 'lang_en'])
            );

        Telegram::bot('mybot')->sendMessage([
            'chat_id'      => $chatId,
            'text'         => $text,
            'reply_markup' => $inlineKeyboard,
        ]);
    }

    public function handleLanguageSelection($chatId, $language)
    {
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

        $user = \App\Models\User::where('chat_id', $chatId)->first();
        Log::info(['user info' => $user]);

        if ($user) {
            // existing user → generate token
            $user->tokens()->delete();
            $token = $user->createToken('api_token', ['*'], now()->addDays(30))->plainTextToken;
            $url = "https://vacancies.inter-ai.uz/#?locale={$langCode}&token={$token}&chat_id={$chatId}";
        } else {
            $url = "https://vacancies.inter-ai.uz/#?locale={$langCode}&chat_id={$chatId}";
        }

        // ✅ Inline button for “Dasturga kirish”
        $inlineKeyboard = Keyboard::make()
            ->inline()
            ->row([
                Keyboard::inlineButton([
                    'text'    => $this->getViewVacanciesText($language),
                    'web_app' => ['url' => $url],
                ]),
            ])
            ->row([
                Keyboard::inlineButton([
                    'text'          => $this->getBackButtonText($language),
                    'callback_data' => 'back',
                ]),
            ]);

        try {
            Telegram::bot('mybot')->sendMessage([
                'chat_id'      => $chatId,
                'text'         => $text,
                'reply_markup' => $inlineKeyboard,
            ]);

            Log::info("handleLanguageSelection => inline message sent successfully!");
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
