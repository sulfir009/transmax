<?php

namespace App\Http\Middleware;

use App\Service\Site;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LanguageMiddleware
{
    /**
     * Поддерживаемые языки сайта
     */
    protected array $supportedLocales = ['en', 'uk', 'ru'];
    
    /**
     * Язык по умолчанию (без префикса в URL)
     */
    protected string $defaultLocale = 'ru';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $segment = $request->segment(1);
        $isAjax = $request->ajax() || $request->expectsJson();

        if ($isAjax) {
            $routeLocale = $request->route('lang');
            if (is_string($routeLocale) && in_array($routeLocale, $this->supportedLocales, true)) {
                $locale = $routeLocale;
            } elseif (in_array($segment, $this->supportedLocales, true)) {
                $locale = $segment;
            } else {
                $locale = session('lang', $this->defaultLocale);
            }

            Site::setLang($locale);
            app()->setLocale($locale);

            $request->attributes->set('locale', $locale);

            view()->share('currentLocale', $locale);
            view()->share('supportedLocales', $this->supportedLocales);
            view()->share('defaultLocale', $this->defaultLocale);

            return $next($request);
        }
        
                if ($segment === 'ua') {
            $path = ltrim($request->path(), '/');
            $path = substr($path, strlen('ua'));
            $path = ltrim($path, '/');

            $queryString = $request->getQueryString();
            $querySuffix = $queryString ? '?' . $queryString : '';

            return redirect('/uk' . ($path !== '' ? '/' . $path : '') . $querySuffix, 301);
        }
        
        // Проверяем, есть ли языковой префикс в URL
        if (in_array($segment, $this->supportedLocales)) {
            $locale = $segment;
        } else {
            // Если префикса нет, используем язык по умолчанию или из сессии
            $locale = session('lang', $this->defaultLocale);
            
            // Если мы на корневом пути и язык не по умолчанию, редирект на версию с префиксом
            if ($locale !== $this->defaultLocale) {
                $path = $request->path();
                if ($path === '/') {
                    $queryString = $request->getQueryString();
                    $querySuffix = $queryString ? '?' . $queryString : '';

                    return redirect('/' . $locale . $querySuffix);
                }
                // Для других путей без префикса, добавляем префикс если язык не по умолчанию
                if (!in_array($request->segment(1), $this->supportedLocales)) {
                    $queryString = $request->getQueryString();
                    $querySuffix = $queryString ? '?' . $queryString : '';

                    return redirect('/' . $locale . '/' . $path . $querySuffix);
                }
            }
        }
        
        // Устанавливаем язык
        Site::setLang($locale);
        app()->setLocale($locale);
        
        // Сохраняем текущий язык в request для использования в других местах
        $request->attributes->set('locale', $locale);
        
        // Добавляем язык в shared данные для view
        view()->share('currentLocale', $locale);
        view()->share('supportedLocales', $this->supportedLocales);
        view()->share('defaultLocale', $this->defaultLocale);
        
        return $next($request);
    }
}
