<?php

namespace App\Http\Middleware;

use App\Service\Site;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLanguageFromUrl
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
        $segments = $request->segments();
        
        // Проверяем, есть ли языковой префикс в URL
        if (count($segments) > 0 && in_array($segments[0], $this->supportedLocales)) {
            $locale = $segments[0];
            
            // Удаляем языковой сегмент из запроса для корректной работы маршрутов
            array_shift($segments);
            $newPath = implode('/', $segments);
            
            // Создаем новый request без языкового префикса
            $request->server->set('REQUEST_URI', '/' . $newPath);
            $request->initialize(
                $request->query->all(),
                $request->request->all(),
                $request->attributes->all(),
                $request->cookies->all(),
                $request->files->all(),
                $request->server->all(),
                $request->getContent()
            );
        } else {
            // Если префикса нет, используем язык по умолчанию или из сессии
            $locale = session('lang', $this->defaultLocale);
        }
        
        // Устанавливаем язык
        Site::setLang($locale);
        app()->setLocale($locale);
        
        // Сохраняем текущий язык в request для использования в других местах
        $request->attributes->set('locale', $locale);
        
        return $next($request);
    }
    
    /**
     * Получить поддерживаемые языки
     */
    public function getSupportedLocales(): array
    {
        return $this->supportedLocales;
    }
    
    /**
     * Получить язык по умолчанию
     */
    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }
}
