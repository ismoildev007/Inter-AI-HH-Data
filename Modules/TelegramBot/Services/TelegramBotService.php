<?php

namespace Modules\TelegramBot\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramBotService
{
    public function sendWelcomeMessage($chatId)
    {
        $text = "Assalomu alaykum! inter-AI vakansiyalari botiga xush kelibsiz!";
       // Log::info("sendWelcomeMessage => chatId: {$chatId}, text: {$text}");

        Telegram::bot('mybot')->sendMessage([
            'chat_id' => $chatId,
            'text'    => $text,
        ]);
    }

    public function sendLanguageSelection($chatId)
    {
        $text = "Iltimos, tilni tanlang / Пожалуйста, выберите язык / Please select a language:";
     //   Log::info("sendLanguageSelection => chatId: {$chatId}");

        $keyboard = Keyboard::make()
            ->inline()
            ->row([
                Keyboard::inlineButton(['text' => '🇺🇿 O\'zbek', 'callback_data' => 'lang_uz']),
                Keyboard::inlineButton(['text' => '🇷🇺 Русский', 'callback_data' => 'lang_ru']),
                Keyboard::inlineButton(['text' => '🇬🇧 English', 'callback_data' => 'lang_en']),
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
     //   Log::info("handleLanguageSelection => chatId: {$chatId}, lang: {$language}");

        $texts = [
            'uz' => 'Platformamizdan foydalanish uchun "Dasturga kirish" tugmasini bosing!',
            'ru' => 'Чтобы использовать нашу платформу, нажмите кнопку «Войти в программу»!',
            'en' => 'To use our platform, please click the "Sign in" button!',
        ];
        $text = $texts[$language] ?? $texts['uz'];

        $langCode = $language;

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

        try {
            Telegram::bot('mybot')->sendMessage([
                'chat_id'      => $chatId,
                'text'         => $text,
                'reply_markup' => $inlineKeyboard,
            ]);

           // Log::info("handleLanguageSelection => message sent successfully!");
        } catch (\Exception $e) {
            Log::error("handleLanguageSelection ERROR: " . $e->getMessage());
        }
    }
    public function setBotCommands()
    {
        $commands = [
            [
                'command' => 'start',
                'description' => 'Botni ishga tushirish / Start the bot',
            ]
        ];

        Telegram::bot('mybot')->setMyCommands([
            'commands' => $commands,
        ]);

     //   Log::info("Bot commands set successfully!");
    }


    public function getViewVacanciesText($lang)
    {
        $texts = [
            'uz' => 'Dasturga kirish',
            'ru' => 'Войти в программу',
            'en' => 'Sign in',
        ];
        return $texts[$lang] ?? 'Kirish';
    }

    /**
     * Send a resume PDF file to a Telegram chat.
     */
    public function sendResumePdf(int|string $chatId, string $path, string $fileName = 'resume.pdf'): void
    {
        if (! is_file($path)) {
            Log::warning("sendResumePdf: file not found", ['chat_id' => $chatId, 'path' => $path]);
            return;
        }

        try {
            Telegram::bot('mybot')->sendDocument([
                'chat_id'  => $chatId,
                'document' => InputFile::create($path, $fileName),
                'caption'  => 'Sizning resume faylingiz',
            ]);
        } catch (\Throwable $e) {
            Log::error('sendResumePdf error: '.$e->getMessage(), [
                'chat_id' => $chatId,
                'path' => $path,
            ]);
        }
    }
}
