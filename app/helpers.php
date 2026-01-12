<?php

use App\Service\Site;
use App\Helpers\LocaleHelper;

if (!function_exists('locale_url')) {
    /**
     * Генерирует URL с языковым префиксом
     *
     * @param string $path
     * @param string|null $locale
     * @return string
     */
    function locale_url(string $path = '', ?string $locale = null): string
    {
        return Site::url($path, $locale);
    }
}

if (!function_exists('locale_route')) {
    /**
     * Генерирует route с учетом языка
     *
     * @param string $name
     * @param mixed $parameters
     * @param bool $absolute
     * @param string|null $locale
     * @return string
     */
    function locale_route(string $name, $parameters = [], bool $absolute = true, ?string $locale = null): string
    {
        return Site::route($name, $parameters, $absolute, $locale);
    }
}

if (!function_exists('switch_language_url')) {
    /**
     * Генерирует URL для переключения языка
     *
     * @param string $locale
     * @return string
     */
    function switch_language_url(string $locale): string
    {
        return Site::switchLanguageUrl($locale);
    }
}

if (!function_exists('current_locale')) {
    /**
     * Получить текущий язык
     *
     * @return string
     */
    function current_locale(): string
    {
        return Site::lang();
    }
}

if (!function_exists('is_locale')) {
    /**
     * Проверить, является ли текущий язык указанным
     *
     * @param string $locale
     * @return bool
     */
    function is_locale(string $locale): bool
    {
        return Site::isLang($locale);
    }
}

if (!function_exists('supported_locales')) {
    /**
     * Получить поддерживаемые языки
     *
     * @return array
     */
    function supported_locales(): array
    {
        return LocaleHelper::getSupportedLocales();
    }
}

if (!function_exists('default_locale')) {
    /**
     * Получить язык по умолчанию
     *
     * @return string
     */
    function default_locale(): string
    {
        return LocaleHelper::getDefaultLocale();
    }
}

if (!function_exists('locale_name')) {
    /**
     * Получить название языка
     *
     * @param string $locale
     * @return string
     */
    function locale_name(string $locale): string
    {
        $names = [
            'ru' => 'Русский',
            'uk' => 'Українська',
            'en' => 'English'
        ];
        
        return $names[$locale] ?? $locale;
    }
}

if (!function_exists('locale_short_name')) {
    /**
     * Получить короткое название языка
     *
     * @param string $locale
     * @return string
     */
    function locale_short_name(string $locale): string
    {
        $names = [
            'ru' => 'РУС',
            'uk' => 'УКР',
            'en' => 'ENG'
        ];
        
        return $names[$locale] ?? strtoupper($locale);
    }
}

if (!function_exists('locale_flag')) {
    /**
     * Получить флаг для языка
     *
     * @param string $locale
     * @return string
     */
    function locale_flag(string $locale): string
    {
        $flags = [
            'ru' => '🇷🇺',
            'uk' => '🇺🇦',
            'en' => '🇬🇧'
        ];
        
        return $flags[$locale] ?? '🏳️';
    }
}
