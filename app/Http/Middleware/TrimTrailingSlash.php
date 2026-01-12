<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrimTrailingSlash
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Убираем слэш только на корне /api/
        $uri = $request->getRequestUri();           // НЕ трогает тело запроса
        if ($uri === '/api/') {
            // permanentRedirect — это 301
            return redirect('/api', 307);
        }

        return $next($request);
    }
}
