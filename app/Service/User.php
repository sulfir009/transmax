<?php

namespace App\Service;

use Illuminate\Support\Facades\Session;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class User
{
    public static function isAuth()
    {
        return $_SESSION['user']['isAuth'] ?? false;
    }

    public static function login()
    {
        $_SESSION['user']['isAuth'] = true;
    }

    public static function logout()
    {
        $_SESSION['user']['isAuth'] = false;
    }
}
