<?php

namespace Modules\TelegramChannel\Actions;

use App\Models\TelegramChannel;

class TransformMessageText
{
    public function handle(string $peer, string $text, ?TelegramChannel $target): string
    {
        $cfg = (array) config('telegramchannel_relay.transforms', []);

        $plain = ltrim($peer, '@');
        $keys = [$peer, '@'.$plain, $plain];

        $rules = null;
        foreach ($keys as $k) {
            if (isset($cfg[$k])) {
                $rules = $cfg[$k];
                break;
            }
        }
        if (!$rules) return $text;

        $vars = [
            '{target_username}' => $target && $target->username ? '@'.$target->username : '{target_username}',
        ];

        foreach ((array) ($rules['replace'] ?? []) as $op) {
            $type = $op['type'] ?? 'regex';
            if ($type === 'regex') {
                $pattern = $op['pattern'] ?? null;
                $to = (string) ($op['to'] ?? '');
                if (is_string($pattern) && $pattern !== '') {
                    $toEval = strtr($to, $vars);
                    $res = @preg_replace($pattern, $toEval, $text);
                    if ($res !== null) {
                        $text = $res;
                    }
                }
            }
        }

        return $text;
    }
}

