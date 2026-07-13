<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Replit's reverse proxy forwards requests with Host: localhost.
 * This middleware rewrites the host/scheme so that ALL URL generation
 * (redirect()->back(), redirect()->route(), $request->url(), etc.)
 * uses the correct public domain from APP_URL.
 */
class ForceCorrectHost
{
    public function handle(Request $request, Closure $next)
    {
        $appUrl = config('app.url');

        if ($appUrl && ! str_starts_with($appUrl, 'http://localhost')) {
            $parsed = parse_url($appUrl);
            $host   = $parsed['host'] ?? null;
            $scheme = $parsed['scheme'] ?? 'https';
            $port   = $parsed['port'] ?? ($scheme === 'https' ? 443 : 80);

            if ($host) {
                $request->server->set('HTTP_HOST',    $host);
                $request->server->set('SERVER_NAME',  $host);
                $request->server->set('SERVER_PORT',  $port);
                $request->server->set('HTTPS',        $scheme === 'https' ? 'on' : '');
                $request->server->set('REQUEST_SCHEME', $scheme);
                $request->headers->set('HOST', $host);
            }
        }

        return $next($request);
    }
}
