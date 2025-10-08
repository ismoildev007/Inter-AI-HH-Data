<?php

namespace Modules\TelegramChannel\Support;

class ContentFingerprint
{
    /**
     * Build a stable hash for the raw Telegram message to short-circuit duplicates.
     */
    public static function raw(string $text): string
    {
        $clean = self::stripChannelMarkers($text);
        $clean = self::normalizeWhitespace($clean);
        return $clean === '' ? '' : sha1($clean);
    }

    /**
     * Build a stable hash for normalized vacancy data (post-GPT).
     */
    public static function normalized(array $normalized): string
    {
        $title = Signature::slug((string) ($normalized['title'] ?? ''));
        $company = Signature::slug((string) ($normalized['company'] ?? ''));
        $phones = Signature::canonPhones((array) ($normalized['contact']['phones'] ?? []));
        $users = Signature::canonUsernames((array) ($normalized['contact']['telegram_usernames'] ?? []));
        $desc = self::normalizeWhitespace((string) ($normalized['description'] ?? ''));

        $payload = implode('|', [
            $title,
            $company,
            implode(',', $phones),
            implode(',', $users),
            $desc,
        ]);

        return $payload === '' ? '' : sha1($payload);
    }

    protected static function stripChannelMarkers(string $text): string
    {
        $text = preg_replace('/https?:\/\/t\.me\/[^\s]+/iu', '', $text);
        $text = preg_replace('/@([a-z0-9_]{3,})\b(?:\s+kanali)?/iu', '@$1', $text);
        $lines = preg_split("/\r\n|\r|\n/", $text);
        $filtered = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || self::looksLikeChannelFooter($trimmed)) {
                continue;
            }

            $filtered[] = $trimmed;
        }

        return implode(' ', $filtered);
    }

    protected static function looksLikeChannelFooter(string $line): bool
    {
        $footerHandles = array_map(
            fn ($handle) => ltrim(mb_strtolower((string) $handle, 'UTF-8'), '@'),
            (array) config('telegramchannel_relay.dedupe.footer_handles', [])
        );
        $haystack = mb_strtolower($line, 'UTF-8');
        foreach ($footerHandles as $handle) {
            if ($handle !== '' && str_contains($haystack, '@' . $handle)) {
                return true;
            }
        }

        if (preg_match('/@[-_a-z0-9]+$/iu', $line)) {
            return mb_strlen($line) < 32;
        }

        if (preg_match('/^👉\s*@/u', $line)) {
            return true;
        }

        return false;
    }

    protected static function normalizeWhitespace(string $text): string
    {
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^\PC\s]/u', '', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }
}
