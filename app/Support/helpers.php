<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

if (!function_exists('setting')) {
    /**
     * Возвращает значение настройки из mt_settings по коду.
     * Берём поле value, если пусто — fallback на title, если и там пусто — возвращаем $default или сам $code.
     */
    function setting(string $code, ?string $default = null): string
    {
        $code = trim($code);

        if ($code === '') {
            return (string)($default ?? '');
        }

        $cacheKey = 'mt_settings:' . $code;

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($code, $default) {
            $row = DB::table('mt_settings')
                ->select(['value', 'title'])
                ->where('code', $code)
                ->first();

            if (!$row) {
                return (string)($default ?? $code);
            }

            $val = is_string($row->value ?? null) ? trim($row->value) : '';
            if ($val !== '') {
                return $val;
            }

            $title = is_string($row->title ?? null) ? trim($row->title) : '';
            if ($title !== '') {
                return $title;
            }

            return (string)($default ?? $code);
        });
    }
}
