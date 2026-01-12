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
                    return redirect('/' . $locale);
                }
                // Для других путей без префикса, добавляем префикс если язык не по умолчанию
                if (!in_array($request->segment(1), $this->supportedLocales)) {
                    return redirect('/' . $locale . '/' . $path);
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
