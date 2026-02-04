<?php

use App\Service\Admin\Path;

if (!isset($_params) || !is_array($_params)) {
    $_params = [];
}

$uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (!is_string($uriPath)) {
    $uriPath = '';
}

$scriptName = isset($_SERVER['SCRIPT_NAME']) ? (string)$_SERVER['SCRIPT_NAME'] : '';

$section = [];
if ($uriPath !== '') {
    $section = (array) Path::getSection($scriptName, $uriPath);
}

$normalizedPath = $uriPath;
if ($normalizedPath !== '') {
    if (substr($normalizedPath, -10) === '/index.php') {
        $normalizedPath = rtrim(dirname($normalizedPath), '/') . '/';
    } else {
        $normalizedPath = rtrim($normalizedPath, '/') . '/';
    }
}

if (!$section && $normalizedPath !== $uriPath) {
    $section = (array) Path::getSection($scriptName, $normalizedPath);
}

if ($section) {
    $_params['access'] = isset($section['access']) ? $section['access'] : null;
    $_params['access_edit'] = isset($section['access_edit']) ? $section['access_edit'] : null;
    if (!isset($_params['title'])) {
        $_params['title'] = isset($section['title']) ? $section['title'] : 'Бонусы клиентов';
    }
} else {
    if (!isset($_params['title'])) {
        $_params['title'] = 'Бонусы клиентов';
    }
}
