<?php

namespace Modules\TelegramChannel\Support;

use Illuminate\Support\Str;

class Signature
{
    public static function fromNormalized(array $n): string
    {
        $title = self::slug((string) ($n['title'] ?? ''));
        $company = self::slug((string) ($n['company'] ?? ''));
        $phones = self::canonPhones((array) ($n['contact']['phones'] ?? []));
        $users  = self::canonUsernames((array) ($n['contact']['telegram_usernames'] ?? []));
        $description = self::sanitizeDescription((string) ($n['description'] ?? ''));

        $key = implode('|', [
            $title,
            $company,
            implode(',', $phones),
            implode(',', $users),
            $description,
        ]);

        return sha1($key);
    }

    public static function slug(string $s): string
    {
        $s = trim(mb_strtolower($s));
        return Str::slug($s, '-');
    }

    public static function canonPhones(array $phones): array
    {
        $out = [];
        foreach ($phones as $p) {
            $p = (string) $p;
            // keep digits and leading + only
            $p = preg_replace('/[^0-9+]/', '', $p);
            // if multiple +, keep only the first
            if (substr_count($p, '+') > 1) {
                $p = '+' . preg_replace('/[^0-9]/', '', $p);
            }
            if ($p !== '') {
                $out[] = $p;
            }
        }
        $out = array_values(array_unique($out));
        sort($out, SORT_STRING);
        return $out;
    }

    public static function canonUsernames(array $users): array
    {
        $out = [];
        foreach ($users as $u) {
            $u = (string) $u;
            $u = ltrim($u, '@');
            $u = trim(mb_strtolower($u));
            if ($u !== '') {
                $out[] = $u;
            }
        }
        $out = array_values(array_unique($out));
        sort($out, SORT_STRING);
        return $out;
    }

    public static function sanitizeDescription(string $description): string
    {
        $description = strip_tags($description);
        $description = html_entity_decode($description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $description = mb_strtolower($description, 'UTF-8');
        $description = preg_replace('/https?:\/\/t\.me\/[^\s]+/iu', '', $description);
        $footerHandles = array_map(
            fn ($handle) => ltrim(mb_strtolower((string) $handle, 'UTF-8'), '@'),
            (array) config('telegramchannel_relay.dedupe.footer_handles', [])
        );
        if (!empty($footerHandles)) {
            $pattern = '/@(?:' . implode('|', array_map('preg_quote', $footerHandles)) . ')\b/iu';
            $description = preg_replace($pattern, '', $description);
        }
        $description = preg_replace('/\s+/u', ' ', $description);
        return trim($description);
    }
}
