<?php

namespace App\Service;

use Illuminate\Support\Facades\Session;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
class Site
{
    static public function lang()
    {
        return $_SESSION['lang'] ?? 'ru';
    }

    static public function isLang(string $lang): bool
    {
        return self::lang() === $lang;
    }

    static public function setLang($lang)
    {
        session()->put('site.last_lang', $lang);
        session()->put('lang', $lang);
        $_SESSION['lang'] = $lang;
    }
}
