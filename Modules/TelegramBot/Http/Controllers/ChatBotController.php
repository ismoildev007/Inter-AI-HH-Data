<?php

namespace Modules\TelegramBot\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\SupportMessage;
use Telegram\Bot\Api;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ChatBotController extends Controller
{
    protected $dailyLimit;

    public function __construct()
    {
        $this->dailyLimit = env('TELEGRAM_DAILY_LIMIT', 10);
    }

    public function handle(Request $request)
    {
        $telegram = new Api(env('TELEGRAM_CHAT_BOT_TOKEN'));
        $update = $telegram->getWebhookUpdate();

        if ($update->isType('message') && $update->message->chat->type === 'private') {
            $chatId = $update->message->chat->id;
            $text   = $update->message->text ?? '';

            $user = $update->message->from ?? null;
            $firstName = $user->first_name ?? '';
            $lastName = $user->last_name ?? '';
            $username = $user->username ?? '';
            $fullName = trim("$firstName $lastName");

            $date = Carbon::now()->format('Y-m-d');
            $cacheKey = "support_daily_count:{$chatId}:{$date}";

            $currentCount = (int) Cache::get($cacheKey, 0);

            if ($currentCount >= $this->dailyLimit) {
                try {
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text'    => "⚠️ Kechirasiz, siz bugunlik limitingizni tugatdingiz. {$this->dailyLimit} ta xabar yubordingiz. Ertaga qayta urinib ko‘ring.",
                    ]);
                } catch (\Exception $e) {
                    Log::error('Telegram send limit exceeded message failed: ' . $e->getMessage());
                }
                return response('ok');
            }

            if (trim($text) === '/start') {
                try {
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text'    => "👋 Assalomu alaykum, {$firstName}!\n\nSavol va takliflaringizni yozib qoldirishingiz mumkin. 😊",
                    ]);
                } catch (\Exception $e) {
                    Log::error('Telegram send /start failed: ' . $e->getMessage());
                }
                return response('ok');
            }


            try {
                $now = Carbon::now();
                $endOfDay = $now->copy()->endOfDay();
                $secondsUntilEndOfDay = $endOfDay->diffInSeconds($now);

                $currentCount = (int) Cache::get($cacheKey, 0);
                $currentCount++;
                Cache::put($cacheKey, $currentCount, $secondsUntilEndOfDay);

                Log::info("User {$chatId} sent message #{$currentCount} of {$this->dailyLimit}");

                if ($currentCount > $this->dailyLimit) {
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text'    => "⚠️ Kechirasiz, siz bugun {$this->dailyLimit} ta xabar yuborishingiz mumkin edi. Iltimos, ertaga qayta urinib ko‘ring.",
                    ]);
                    return response('ok');
                }
            } catch (\Exception $e) {
                Log::error('Cache increment error: ' . $e->getMessage());
            }

            try {
                $now = Carbon::now();
                $endOfDay = $now->copy()->endOfDay();
                $secondsUntilEndOfDay = $endOfDay->diffInSeconds($now);

                $currentCount = (int) Cache::get($cacheKey, 0);
                $currentCount++;
                Cache::put($cacheKey, $currentCount, $secondsUntilEndOfDay);

                Log::info("User {$chatId} sent message #{$currentCount} of {$this->dailyLimit}");

                $support = SupportMessage::create([
                    'user_chat_id' => $chatId,
                    'message_text' => $text,
                    'status'       => 'pending',
                ]);

                $response = $telegram->sendMessage([
                    'chat_id' => env('TELEGRAM_ADMIN_GROUP_ID'),
                    'text'    => "📩 Yangi murojaat\n\n"
                        ."👤 Foydalanuvchi: {$fullName}\n"
                        .($username ? "🔗 Telegram: @{$username}\n" : '')
                        ."💬 Xabar: {$text}",
                ]);

                $telegramMessageId = $response->getMessageId();

                $support->update([
                    'telegram_message_id' => $telegramMessageId
                ]);

                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text'    => "{$firstName}!\nSavol va taklifingiz uchun rahmat \ntez orada sizga javob beramiz. 🙂",
                ]);
            } catch (\Exception $e) {
                Log::error('Telegram send or save message failed: ' . $e->getMessage());
                return response('ok', 500);
            }

        }

        if ($update->isType('message') && $update->message->chat->id == env('TELEGRAM_ADMIN_GROUP_ID')) {
            if (isset($update->message->reply_to_message)) {
                $origMsg = $update->message->reply_to_message;
                $replyText = $update->message->text ?? '';

                $origMessageId = $origMsg->message_id;

                $support = SupportMessage::where('telegram_message_id', $origMessageId)
                    ->where('status','pending')
                    ->first();

                if ($support) {
                    try {
                        $telegram->sendMessage([
                            'chat_id' => $support->user_chat_id,
                            'text'    => "👨‍💼 Inter-AI Support:\n\n" . $replyText
                        ]);

                        $support->update([
                            'status' => 'answered'
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Telegram send reply to user failed: ' . $e->getMessage());
                    }
                }
            }

            return response('ok');
        }

        return response('ok');
    }
}
