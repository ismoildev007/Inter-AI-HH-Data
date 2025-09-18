<?php

namespace Modules\TelegramChannel\Actions;

class ExtractTextFromMessage
{
    public function handle(array $msg): ?string
    {
        // MadelineProto xabarda matn/caption odatda 'message' maydonida
        $text = trim((string) ($msg['message'] ?? ''));

        // Bo'sh bo'lsa skip
        if ($text === '') {
            return null;
        }

        // 3+ bo'sh qatordan bitta-ikkitaga normallashtiramiz
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }
}