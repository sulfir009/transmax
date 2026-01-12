<?php

namespace App\Service;

use Illuminate\Support\Facades\Session;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Admin
{
    public static function isAuth()
    {
        return $_SESSION['admin'] ?? false;
    }
}
