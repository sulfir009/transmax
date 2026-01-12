<?php
// Test file for Xdebug configuration

// Show PHP info
phpinfo();

// Test xdebug
if (extension_loaded('xdebug')) {
    echo  "\n\nXdebug is loaded!\n";
    echo  "Xdebug version: " . phpversion('xdebug') . "\n";

    // Show xdebug settings
    $xdebug_settings = [
        'xdebug.mode' => ini_get('xdebug.mode'),
        'xdebug.client_host' => ini_get('xdebug.client_host'),
        'xdebug.client_port' => ini_get('xdebug.client_port'),
        'xdebug.start_with_request' => ini_get('xdebug.start_with_request'),
        'xdebug.idekey' => ini_get('xdebug.idekey'),
        'xdebug.log' => ini_get('xdebug.log'),
    ];

    echo  "\nXdebug Settings:\n";
    foreach ($xdebug_settings as $key => $value) {
        echo  "$key = $value\n";
    }

    // Test breakpoint
    $test = "Xdebug breakpoint test"; // Set breakpoint here
    echo  "\nBreakpoint test: $test\n";

} else {
    echo  "Xdebug is NOT loaded!\n";
}
