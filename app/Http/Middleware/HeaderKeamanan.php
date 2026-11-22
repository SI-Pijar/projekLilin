<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HeaderKeamanan
{
    private const KEBIJAKAN_ISI = "default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; font-src 'self'; script-src 'self' 'unsafe-inline'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'";

    public function handle(Request $request, Closure $next): Response
    {
        $respons = $next($request);

        $respons->headers->set('X-Frame-Options', 'DENY');
        $respons->headers->set('X-Content-Type-Options', 'nosniff');
        $respons->headers->set('Referrer-Policy', 'same-origin');
        $respons->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $respons->headers->set('Content-Security-Policy', self::KEBIJAKAN_ISI);

        if ($request->isSecure()) {
            $respons->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        header_remove('X-Powered-By');

        return $respons;
    }
}