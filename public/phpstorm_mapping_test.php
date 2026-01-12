<?php
// XDEBUG TEST - НЕ УДАЛЯЙТЕ ЭТОТ ФАЙЛ!
// Этот файл помогает настроить path mappings в PHPStorm

// Убедитесь, что Xdebug запущен
xdebug_break();  // Принудительная остановка для PHPStorm

echo "Current file: " . __FILE__ . "\n";
echo "This should be: /var/www/public/phpstorm_mapping_test.php\n\n";

// ПОСТАВЬТЕ BREAKPOINT НА СЛЕДУЮЩЕЙ СТРОКЕ
$test = "Breakpoint test";
echo "Test variable: $test\n";

phpinfo();
