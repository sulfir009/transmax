<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

class LocaleHelper
{
    /**
     * Поддерживаемые языки
     */
    protected static array $supportedLocales = ['en', 'uk', 'ru'];
    
    /**
     * Язык по умолчанию
     */
    protected static string $defaultLocale = 'ru';
    
    /**
     * Генерирует URL с языковым префиксом
     *
     * @param string $path
     * @param string|null $locale
     * @return string
     */
    public static function localizedUrl(string $path = '', ?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        
        // Удаляем начальный слеш, если есть
        $path = ltrim($path, '/');
        
        // Удаляем существующий языковой префикс из пути, если он есть
        foreach (self::$supportedLocales as $lang) {
            if (str_starts_with($path, $lang . '/')) {
                $path = substr($path, strlen($lang) + 1);
            }
        }
        
        // Для языка по умолчанию не добавляем префикс
        if ($locale === self::$defaultLocale) {
            return url($path);
        }
        
        // Для других языков добавляем префикс
        return url($locale . '/' . $path);
    }
    
    /**
     * Генерирует URL для переключения языка
     *
     * @param string $locale
     * @return string
     */
    public static function switchLanguageUrl(string $locale): string
    {
        $currentPath = request()->path();
        
        // Удаляем текущий языковой префикс
        foreach (self::$supportedLocales as $lang) {
            if (str_starts_with($currentPath, $lang . '/')) {
                $currentPath = substr($currentPath, strlen($lang) + 1);
                break;
            } elseif ($currentPath === $lang) {
                $currentPath = '';
                break;
            }
        }
        
        return self::localizedUrl($currentPath, $locale);
    }
    
    /**
     * Получить текущий язык
     *
     * @return string
     */
    public static function getCurrentLocale(): string
    {
        return app()->getLocale();
    }
    
    /**
     * Проверить, является ли язык текущим
     *
     * @param string $locale
     * @return bool
     */
    public static function isCurrentLocale(string $locale): bool
    {
        return app()->getLocale() === $locale;
    }
    
    /**
     * Получить поддерживаемые языки
     *
     * @return array
     */
    public static function getSupportedLocales(): array
    {
        return self::$supportedLocales;
    }
    
    /**
     * Получить язык по умолчанию
     *
     * @return string
     */
    public static function getDefaultLocale(): string
    {
        return self::$defaultLocale;
    }
    
    /**
     * Генерирует route с учетом языка
     *
     * @param string $name
     * @param mixed $parameters
     * @param bool $absolute
     * @param string|null $locale
     * @return string
     */
    public static function localizedRoute(string $name, $parameters = [], bool $absolute = true, ?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        
        // Для языка по умолчанию используем обычный route
        if ($locale === self::$defaultLocale) {
            return route($name, $parameters, $absolute);
        }
        
        // Для других языков добавляем префикс к имени роута
        $localizedRouteName = $locale . '.' . $name;
        
        // Проверяем, существует ли локализованный роут
        if (Route::has($localizedRouteName)) {
            return route($localizedRouteName, $parameters, $absolute);
        }
        
        // Если локализованного роута нет, генерируем URL вручную
        $url = route($name, $parameters, $absolute);
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';
        
        // Добавляем языковой префикс
        $localizedPath = '/' . $locale . $path;
        
        // Собираем URL обратно
        $result = '';
        if (isset($parsedUrl['scheme']) && isset($parsedUrl['host'])) {
            $result = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            if (isset($parsedUrl['port'])) {
                $result .= ':' . $parsedUrl['port'];
            }
        }
        $result .= $localizedPath;
        
        if (isset($parsedUrl['query'])) {
            $result .= '?' . $parsedUrl['query'];
        }
        if (isset($parsedUrl['fragment'])) {
            $result .= '#' . $parsedUrl['fragment'];
        }
        
        return $result;
    }
}
