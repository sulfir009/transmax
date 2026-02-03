<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UrlNormalizeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $path = $request->getPathInfo();
        $query = $request->getQueryString();
        $scheme = $request->getScheme();

        $targetHost = str_starts_with($host, 'www.') ? substr($host, 4) : $host;
        $targetScheme = 'https';
        $lowerPath = preg_match('/[A-Z]/', $path) ? strtolower($path) : $path;

        $shouldRedirect = $host !== $targetHost || $scheme !== $targetScheme || $lowerPath !== $path;

        if ($shouldRedirect) {
            $targetUrl = $targetScheme . '://' . $targetHost . $lowerPath;
            if ($query) {
                $targetUrl .= '?' . $query;
            }

            return redirect()->to($targetUrl, 301);
        }

        return $next($request);
    }
}