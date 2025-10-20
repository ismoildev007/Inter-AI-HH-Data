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

        if ($update->isType('message') && $update->message->chat->type === 'private') {
            $chatId = $update->message->chat->id;
            $text   = $update->message->text;

            $user = $update->message->from;
            $firstName = $user->first_name ?? '';
            $lastName = $user->last_name ?? '';
            $username = $user->username ?? '';
            $fullName = trim("$firstName $lastName");


            if ($text === '/start') {
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text'    => "Salom $firstName! Qanday yordam bera olaman?"
                ]);
                return response('ok');
            }

            $support = SupportMessage::create([
                'user_chat_id'    => $chatId,
                'message_text'    => $text,
                'status'          => 'pending',
            ]);

            $response = $telegram->sendMessage([
                'chat_id' => env('TELEGRAM_ADMIN_GROUP_ID'),
                'text'    => "🧑‍💼 Foydalanuvchi: *{$fullName}*\n".
                    ($username ? "(@{$username})\n" : '').
                    " xabar qoldirdi:\n\n".
                    $text,
                'parse_mode' => 'Markdown'
            ]);

            $telegramMessageId = $response->getMessageId();

            $support->update([
                'telegram_message_id' => $telegramMessageId
            ]);

            return response('ok');
        }

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
                        'text'    => "Admin javobi:\n\n" . $replyText
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
