<?php

namespace App\Service;

use Illuminate\Support\Facades\Session;
if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $domain = function_exists('config') ? config('session.domain') : (getenv('SESSION_DOMAIN') ?: '');
    if (!headers_sent()) {
        session_set_cookie_params(0, '/', $domain ?: '', $secure, true);
    }
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
        
        if (function_exists('app')) {
            app()->setLocale($lang);
        }
    }
}
