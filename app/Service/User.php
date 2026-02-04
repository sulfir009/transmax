<?php

namespace App\Service;

use Illuminate\Support\Facades\Session;

if (session_status() === PHP_SESSION_NONE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $domain = function_exists('config') ? config('session.domain') : (getenv('SESSION_DOMAIN') ?: '');
    if (!headers_sent()) {
        session_set_cookie_params(0, '/', $domain ?: '', $secure, true);
    }
    session_start();
}

class User
{
    public static function isAuth()
    {
        if (!empty($_SESSION['user']['isAuth'])) {
            return true;
        }
        return !empty($_SESSION['user']['auth']);
    }

    public static function login()
    {
        $_SESSION['user']['isAuth'] = true;
        $_SESSION['user']['auth'] = true;
    }

    public static function logout()
    {
        $_SESSION['user']['isAuth'] = false;
        $_SESSION['user']['auth'] = false;
        unset($_SESSION['user']['crypt'], $_SESSION['user']['id'], $_SESSION['user']['email'], $_SESSION['user']['phone'], $_SESSION['user']['phone_code'], $_SESSION['user']['uid']);
        if (!headers_sent()) {
            $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            $domain = function_exists('config') ? config('session.domain') : (getenv('SESSION_DOMAIN') ?: '');
            $expire = time() - 3600;
            setcookie('mt_client_id', '', $expire, '/', $domain ?: '', $secure, true);
            setcookie('mt_client_uid', '', $expire, '/', $domain ?: '', $secure, true);
            setcookie('mt_client_email', '', $expire, '/', $domain ?: '', $secure, true);
            setcookie('mt_client_phone', '', $expire, '/', $domain ?: '', $secure, true);
            setcookie('mt_client_phone_code', '', $expire, '/', $domain ?: '', $secure, true);
        }
    }
}
