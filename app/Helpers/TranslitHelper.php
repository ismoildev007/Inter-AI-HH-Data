<?php

namespace App\Helpers;

class TranslitHelper
{
    protected static array $latin = [
        'a','b','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','x','y','z','ʼ','‘','’','`','‘','’'
    ];

    protected static array $cyrillic = [
        'а','б','д','е','ф','г','ҳ','и','ж','к','л','м','н','о','п','қ','р','с','т','у','в','х','й','з',"ъ","‘","’","ʼ","`","‘","’"
    ];

    public static function toCyrillic(string $text): string
    {
        $map = [
            'ya' => 'я','yu' => 'ю','ch' => 'ч','sh' => 'ш','yo' => 'ё','o‘' => 'ў','g‘' => 'ғ',
            'Ya' => 'Я','Yu' => 'Ю','Ch' => 'Ч','Sh' => 'Ш','Yo' => 'Ё','O‘' => 'Ў','G‘' => 'Ғ',
        ];

        $text = strtr($text, $map);
        return str_replace(self::$latin, self::$cyrillic, $text);
    }

    public static function toLatin(string $text): string
    {
        $map = [
            'я' => 'ya','ю' => 'yu','ч' => 'ch','ш' => 'sh','ё' => 'yo','ў' => 'o‘','ғ' => 'g‘',
            'Я' => 'Ya','Ю' => 'Yu','Ч' => 'Ch','Ш' => 'Sh','Ё' => 'Yo','Ў' => 'O‘','Ғ' => 'G‘',
        ];

        $text = strtr($text, $map);
        return str_replace(self::$cyrillic, self::$latin, $text);
    }
}
