<?php


namespace App\Tests\Helpers;


class NormalizeJson
{
    public static function normalize(string $str)
    {
        $str = preg_replace_callback('/\\\\u([a-f0-9]{4})/i', function($m){ return chr(hexdec($m[1])-1072+224);}, $str);
        return iconv('cp1251', 'utf-8', $str);
    }
}