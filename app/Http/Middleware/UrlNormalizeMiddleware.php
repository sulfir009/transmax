<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class UrlNormalizeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $path = $request->getPathInfo();
        $query = $request->getQueryString();
        $scheme = $request->getScheme();

        // API endpoints must not be redirected between hosts/schemes because
        // mobile clients send JSON POST payloads and may lose method/body on redirects.
        if ($path === '/api' || $path === '/api/' || str_starts_with($path, '/api/')) {
            return $next($request);
        }

        $targetHost = str_starts_with($host, 'www.') ? substr($host, 4) : $host;
        $targetScheme = 'https';
        $lowerPath = preg_match('/[A-Z]/', $path) ? strtolower($path) : $path;

        $shouldRedirect = $host !== $targetHost || $scheme !== $targetScheme || $lowerPath !== $path;

        if ($shouldRedirect) {
            $targetUrl = $targetScheme . '://' . $targetHost . $lowerPath;
            if ($query) {
                $targetUrl .= '?' . $query;
            }

            Log::info('[UrlNormalize] redirect', [
                'from' => $scheme . '://' . $host . $path . ($query ? '?' . $query : ''),
                'to' => $targetUrl,
            ]);

            $status = in_array($request->getMethod(), ['GET', 'HEAD'], true) ? 301 : 308;

            return redirect()->to($targetUrl, $status);
        }

        return $next($request);
    }
}
