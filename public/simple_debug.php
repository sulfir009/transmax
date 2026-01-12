<?php
// Простой тест Xdebug для PHPStorm

// Включаем отображение ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Принудительно запускаем сессию debug
if (!isset($_GET['XDEBUG_SESSION'])) {
    header('Location: ' . $_SERVER['PHP_SELF'] . '?XDEBUG_SESSION=PHPSTORM');
    exit;
}

echo "<h1>Xdebug Simple Test</h1>";

// Проверяем загружен ли Xdebug
if (!extension_loaded('xdebug')) {
    die("<p style='color:red'>❌ Xdebug NOT loaded!</p>");
}

echo "<p style='color:green'>✅ Xdebug is loaded</p>";

// Показываем настройки
echo "<h2>Current Settings:</h2>";
echo "<pre>";
$settings = [
    'xdebug.mode' => ini_get('xdebug.mode'),
    'xdebug.client_host' => ini_get('xdebug.client_host'),
    'xdebug.client_port' => ini_get('xdebug.client_port'),
    'xdebug.idekey' => ini_get('xdebug.idekey'),
];
print_r($settings);
echo "</pre>";

// ПОСТАВЬТЕ BREAKPOINT НА СЛЕДУЮЩЕЙ СТРОКЕ
$test = "If execution stops here, debugging works!";
echo "<h2>Test variable:</h2>";
echo "<p>$test</p>";

// Тест функции
function debugTest($message) {
    // ЕЩЕ ОДИН ХОРОШИЙ BREAKPOINT
    return "Debug message: " . $message;
}

$result = debugTest("PHPStorm Xdebug is working");
echo "<p>$result</p>";

echo "<hr>";
echo "<p>Session cookie: " . ($_COOKIE['XDEBUG_SESSION'] ?? 'not set') . "</p>";
