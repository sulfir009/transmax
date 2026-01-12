<?php
session_start();

echo "<h1>Session Test</h1>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session status: " . session_status() . "\n";
echo "Session data: " . print_r($_SESSION, true) . "\n";

// Проверяем авторизацию пользователя
if (isset($_SESSION['user']['crypt'])) {
    echo "<h2 style='color: green;'>User is AUTHENTICATED!</h2>";
    echo "User crypt: " . $_SESSION['user']['crypt'];
} else {
    echo "<h2 style='color: red;'>User is NOT authenticated</h2>";
}

// Проверяем ошибки аутентификации
if (isset($_SESSION['invalid_social_auth'])) {
    echo "<h2 style='color: orange;'>Social auth error: " . $_SESSION['invalid_social_auth'] . "</h2>";
}

echo "</pre>";

echo "<br><a href='/'>Go to home</a>";
echo "<br><a href='/majbutni-pozdki'>Go to private area</a>";
?>