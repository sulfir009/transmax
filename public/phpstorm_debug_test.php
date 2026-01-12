<?php
/**
 * PHPStorm Xdebug Test with Zero Configuration
 *
 * Instructions:
 * 1. Start PHPStorm and open this project
 * 2. Click the phone icon (Start Listening for PHP Debug Connections)
 * 3. Set a breakpoint on line marked below
 * 4. Open http://maxtranceltd.local/phpstorm_debug_test.php
 * 5. PHPStorm should automatically detect the connection
 */

// Force Xdebug to start
if (extension_loaded('xdebug')) {
    ini_set('xdebug.mode', 'debug');
    ini_set('xdebug.start_with_request', 'yes');
}

echo "<h1>PHPStorm Xdebug Test</h1>";
echo "<pre>";

// Display current configuration
$config = [
    'PHP Version' => phpversion(),
    'Xdebug Version' => phpversion('xdebug'),
    'Xdebug Mode' => ini_get('xdebug.mode'),
    'IDE Key' => ini_get('xdebug.idekey'),
    'Client Host' => ini_get('xdebug.client_host'),
    'Client Port' => ini_get('xdebug.client_port'),
];

foreach ($config as $key => $value) {
    echo  sprintf("%-15s: %s\n", $key, $value ?: 'not set');
}

echo "\n--- Breakpoint Test ---\n";

// SET BREAKPOINT HERE ↓
$testVariable = "If PHPStorm stops here, Xdebug is working!";
echo $testVariable . "\n";

// Test function call stack
function testFunction($param) {
    return "Function called with: " . $param; // Another good breakpoint location
}

$result = testFunction("PHPStorm Debug Test");
echo $result . "\n";

echo "\n--- Test Complete ---\n";
echo "</pre>";

// Add debug trigger for browser extensions
if (isset($_GET['XDEBUG_SESSION_START'])) {
    echo  "<p style='color: green;'>✓ Xdebug session started via GET parameter</p>";
}

// Show how to trigger debugging
echo "<h2>Debug Triggers:</h2>";
echo "<ul>";
echo "<li><a href='?XDEBUG_SESSION_START=PHPSTORM'>Start Debug Session</a></li>";
echo "<li><a href='?XDEBUG_SESSION_STOP=1'>Stop Debug Session</a></li>";
echo "</ul>";

echo "<h2>Browser Extension:</h2>";
echo "<p>For easier debugging, install Xdebug Helper browser extension and set IDE key to: <strong>PHPSTORM</strong></p>";
