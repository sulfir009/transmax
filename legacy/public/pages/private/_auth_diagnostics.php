<?php

use Illuminate\Support\Facades\Log;

if (!function_exists('mt_mask_cookie_value')) {
    function mt_mask_cookie_value($value)
    {
        $value = (string)$value;
        $len = strlen($value);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }
        return substr($value, 0, 2) . str_repeat('*', max(1, $len - 4)) . substr($value, -2);
    }
}

if (!function_exists('mt_collect_masked_cookies')) {
    function mt_collect_masked_cookies($cookies)
    {
        $masked = [];
        if (!is_array($cookies)) {
            return $masked;
        }
        foreach ($cookies as $name => $value) {
            $masked[$name] = mt_mask_cookie_value($value);
        }
        return $masked;
    }
}

if (!function_exists('mt_cabinet_auth_log')) {
    function mt_cabinet_auth_log($User, $Router, $context = 'cabinet')
    {
        try {
            $locale = null;
            if (isset($Router) && isset($Router->lang)) {
                $locale = $Router->lang;
            }
            if (!$locale && class_exists('\\App\\Service\\Site')) {
                $locale = \App\Service\Site::lang();
            }

            $logData = [
                'context' => $context,
                'locale' => $locale,
                'url' => $_SERVER['REQUEST_URI'] ?? null,
                'session_id' => session_id(),
                'cookies' => mt_collect_masked_cookies($_COOKIE ?? []),
                'user' => [
                    'id' => isset($User->id) ? (int)$User->id : 0,
                    'email' => isset($User->email) ? $User->email : null,
                    'phone' => isset($User->phone) ? $User->phone : null,
                    'uid' => isset($User->uid) ? $User->uid : null,
                    'auth_source' => isset($User->authSource) ? $User->authSource : null,
                ],
                'is_auth' => \App\Service\User::isAuth() ? 1 : 0,
            ];

            Log::channel('cabinet')->info('[CABINET AUTH]', $logData);
        } catch (Exception $e) {
            error_log('[CABINET AUTH] log failed: ' . $e->getMessage());
        }
    }
}
