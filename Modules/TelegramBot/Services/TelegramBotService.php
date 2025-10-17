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
        Cache::put("lang_{$chatId}", $language, now()->addHours(24));
        Log::info("handleLanguageSelection => chatId: {$chatId}, lang: {$language}");

        $texts = [
            '🇺🇿 O\'zbek' => 'Til tanlandi ✅ Platformamizdan ro‘yxatdan o‘tish uchun quyidagi tugmani bosing!',
            '🇷🇺 Русский' => 'Язык выбран ✅ Нажмите кнопку ниже, чтобы зарегистрироваться на нашей платформе!',
            '🇬🇧 English' => 'Language selected ✅ Click the button below to register on our platform!',
        ];
        $text = $texts[$language] ?? $texts['🇺🇿 O\'zbek'];

        $langCodeMap = [
            '🇺🇿 O\'zbek' => 'uz',
            '🇷🇺 Русский' => 'ru',
            '🇬🇧 English' => 'en',
        ];
        $langCode = $langCodeMap[$language] ?? 'uz';

        $user = User::where('chat_id', $chatId)->first();

        if (!$user) {
            $webAppUrl = "https://vacancies.inter-ai.uz/#/register?locale={$langCode}&chat_id={$chatId}";
        } else {
            $token = $user->createToken('api_token', ['*'], now()->addYears(22))->plainTextToken;
            $webAppUrl = "https://vacancies.inter-ai.uz/#?locale={$langCode}&token={$token}&chat_id={$chatId}";
        }

        $inlineKeyboard = Keyboard::make()
            ->inline()
            ->row([
                Keyboard::inlineButton([
                    'text'    => $this->getViewProductsText($language),
                    'web_app' => ['url' => $webAppUrl],
                ]),
            ]);

        $backKeyboard = Keyboard::make()
            ->setResizeKeyboard(true)
            ->row([Keyboard::button($this->getBackButtonText($language))]);

        try {
            Telegram::bot('mybot')->sendMessage([
                'chat_id'      => $chatId,
                'text'         => $text,
                'reply_markup' => $inlineKeyboard,
            ]);
    
            // 2️⃣ Send “Back” button message separately
            Telegram::bot('mybot')->sendMessage([
                'chat_id'      => $chatId,
                'text'         => "⬅️",
                'reply_markup' => $backKeyboard,
            ]);

            Log::info("handleLanguageSelection => messages sent successfully!");
        } catch (\Exception $e) {
            Log::error("handleLanguageSelection ERROR: " . $e->getMessage());
        }
    }


    public function getViewProductsText($language)
    {
        $texts = [
            '🇺🇿 O\'zbek' => 'Ro\'yxatdan o\'tish',
            '🇷🇺 Русский' => 'Зарегистрироваться',
            '🇬🇧 English' => 'Sign up',
        ];
        return $texts[$language] ?? 'Ro\'yxatdan o\'tish';
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
