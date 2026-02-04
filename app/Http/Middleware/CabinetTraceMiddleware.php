<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CabinetTraceMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        $routeName = $route?->getName();
        $routeUri = method_exists($route, 'uri') ? $route->uri() : null;
        $routeAction = $route?->getActionName();
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;

        Log::channel('cabinet')->info('[CABINET_TRACE_IN]', [
            'full_url' => $request->fullUrl(),
            'path' => $request->path(),
            'referer' => $request->headers->get('referer'),
            'locale' => $request->attributes->get('locale', app()->getLocale()),
            'session_id' => $sessionId,
            'route_name' => $routeName,
            'route_uri' => $routeUri,
            'route_action' => $routeAction,
            'auth_user_id' => auth()->id(),
            'is_ajax' => $request->ajax() || $request->expectsJson(),
        ]);

        $response = $next($request);

        $location = null;
        if ($response->isRedirection()) {
            $location = $response->headers->get('Location');
        }

        Log::channel('cabinet')->info('[CABINET_TRACE_OUT]', [
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'redirect' => $response->isRedirection() ? 1 : 0,
            'location' => $location,
            'route_name' => $routeName,
            'route_uri' => $routeUri,
            'route_action' => $routeAction,
            'auth_user_id' => auth()->id(),
            'is_ajax' => $request->ajax() || $request->expectsJson(),
        ]);

        return $response;
    }
}
