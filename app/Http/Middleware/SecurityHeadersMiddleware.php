<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Content-Type', 'text/html; charset=utf-8');
        $response->headers->set('Cache-Control', 'public, max-age=31536000');
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');
        $response->headers->remove('Pragma');
        $response->headers->remove('Expires');

        return $response;
    }
}
