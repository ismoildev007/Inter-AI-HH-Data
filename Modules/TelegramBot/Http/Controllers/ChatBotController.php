<?php

namespace Modules\TelegramBot\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\SupportMessage;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Api;

class ChatBotController extends Controller
{
    public function handle(Request $request)
    {
        $telegram = new Api(env('TELEGRAM_CHAT_BOT_TOKEN'));
        $update = $telegram->getWebhookUpdate();

        // 1️⃣ Foydalanuvchi xabari (private chat)
        if ($update->isType('message') && $update->message->chat->type === 'private') {
            $chatId = $update->message->chat->id;
            $text   = $update->message->text;

            // Foydalanuvchi ma'lumotlari
            $user = $update->message->from;
            $firstName = $user->first_name ?? '';
            $lastName = $user->last_name ?? '';
            $username = $user->username ?? '';
            $fullName = trim("$firstName $lastName");

            // ✅ START komandasi
            if ($text === '/start') {
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text'    => "👋 Assalomu alaykum, $firstName!\n\nIltimos, savolingiz yoki taklifingizni yozib qoldiring, tez orada siz bilan bog‘lanamiz 😊",
                    'parse_mode' => 'Markdown'
                ]);
                return response('ok');
            }

            // ✅ Foydalanuvchi xabarini saqlaymiz
            $support = SupportMessage::create([
                'user_chat_id' => $chatId,
                'message_text' => $text,
                'status'       => 'pending',
            ]);

            // ✅ Admin guruhiga yuborish
            $response = $telegram->sendMessage([
                'chat_id' => env('TELEGRAM_ADMIN_GROUP_ID'),
                'text'    => "📩 *Yangi murojaat*\n\n"
                    ."👤 Foydalanuvchi: *{$fullName}*\n"
                    .($username ? "🔗 Telegram: [@{$username}](https://t.me/{$username})\n" : '')
                    ."💬 Xabar: {$text}",
                'parse_mode' => 'Markdown'
            ]);

            $telegramMessageId = $response->getMessageId();

            $support->update([
                'telegram_message_id' => $telegramMessageId
            ]);

            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text'    => "✅ Rahmat, $firstName!\nSizning savolingiz qabul qilindi.\n"
                    ."Bizning jamoa tez orada siz bilan bog‘lanadi. 🙂",
                'parse_mode' => 'Markdown'
            ]);

            return response('ok');
        }

        // 2️⃣ Admin javobi
        if ($update->isType('message') && $update->message->chat->id == env('TELEGRAM_ADMIN_GROUP_ID')) {
            if (isset($update->message->reply_to_message)) {
                $origMsg = $update->message->reply_to_message;
                $replyText = $update->message->text;

                $origMessageId = $origMsg->message_id;

                $support = SupportMessage::where('telegram_message_id', $origMessageId)
                    ->where('status','pending')
                    ->first();

                if ($support) {
                    $telegram->sendMessage([
                        'chat_id' => $support->user_chat_id,
                        'text'    => "👨‍💼 Admin javobi:\n\n" . $replyText
                    ]);

                    $support->update([
                        'status' => 'answered'
                    ]);
                }
            }

            return response('ok');
        }

        return response('ok');
    }

}
