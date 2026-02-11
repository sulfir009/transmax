<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceLocale
{
    public function handle(Request $request, Closure $next, string $locale)
    {
        // Принудительно задаём локаль для текущего запроса
        app()->setLocale($locale);

        return $next($request);
    }
}
