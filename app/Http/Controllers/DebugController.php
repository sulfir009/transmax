<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DebugController extends Controller
{
    public function sessionDebug(Request $request)
    {
        session_start();

        echo "<h1>Session Debug Information</h1>";
        echo "<pre>";
        echo "Session ID: " . session_id() . "\n";
        echo "Session status: " . session_status() . "\n";
        echo "Session data: " . print_r($_SESSION, true) . "\n";
        echo "Request data: " . print_r($request->all(), true) . "\n";

        // Проверяем авторизацию пользователя
        if (isset($_SESSION['user']['crypt'])) {
            echo "<h2 style='color: green;'>✅ User is AUTHENTICATED!</h2>";
            echo "User crypt: " . $_SESSION['user']['crypt'];
        } else {
            echo "<h2 style='color: red;'>❌ User is NOT authenticated</h2>";
        }

        // Проверяем ошибки аутентификации
        if (isset($_SESSION['invalid_social_auth'])) {
            echo "<h2 style='color: orange;'>⚠️ Social auth error: " . $_SESSION['invalid_social_auth'] . "</h2>";
        }

        echo "</pre>";

        echo "<br><hr>";
        echo "<h2>Navigation Links:</h2>";
        echo "<a href='/' style='display:block;margin:10px;'>🏠 Go to home</a>";
        echo "<a href='/majbutni-pozdki' style='display:block;margin:10px;'>👤 Go to private area</a>";
        echo "<a href='/avtorizaciya' style='display:block;margin:10px;'>🔐 Go to login page</a>";

        // Добавляем информацию о Laravel сессии
        echo "<br><hr>";
        echo "<h2>Laravel Session:</h2>";
        echo "<pre>";
        echo "Laravel session ID: " . $request->session()->getId() . "\n";
        echo "Laravel session data: " . print_r($request->session()->all(), true) . "\n";
        echo "</pre>";
    }
}