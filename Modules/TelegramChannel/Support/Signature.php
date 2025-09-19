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

        $key = implode('|', [
            $title,
            $company,
            implode(',', $phones),
            implode(',', $users),
        ]);

        return sha1($key);
    }

    protected static function slug(string $s): string
    {
        $s = trim(mb_strtolower($s));
        return Str::slug($s, '-');
    }

    protected static function canonPhones(array $phones): array
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

    protected static function canonUsernames(array $users): array
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
}

