<?php

namespace Modules\TelegramChannel\Actions;

class ChannelRuleMatcher
{
    public function matches(string $username, string $text): bool
    {
        $rules = (array) config('telegramchannel_relay.rules', []);

        // Username'ni normalizatsiya qilamiz: '@name' va 'name' variantlari
        $key = $username;
        $plain = ltrim($username, '@');
        $candidates = [$key, $plain, '@'.$plain];

        $rule = null;
        foreach ($candidates as $c) {
            if (isset($rules[$c])) {
                $rule = $rules[$c];
                break;
            }
        }

        // Agar qoida yo'q bo'lsa — istisnosiz qabul qilamiz
        if ($rule === null) {
            return true;
        }
        $pattern = (string) ($rule['pattern'] ?? '');
        $ci = !empty($rule['case_insensitive']);

        // Case normalizatsiya kerak bo'lsa:
        $haystack = $ci ? mb_strtolower($text) : $text;
        $needle   = $ci ? mb_strtolower($pattern) : $pattern;

        switch ($rule['type'] ?? null) {
            case 'starts_with':
                return mb_substr($haystack, 0, mb_strlen($needle)) === $needle;

            case 'contains':
                return mb_strpos($haystack, $needle) !== false;

            default:
                return false;
        }
    }
}
