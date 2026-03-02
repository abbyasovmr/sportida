<?php
// app/Http/Middleware/SecurityHeaders.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Предотвращает Clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');
        
        // Предотвращает MIME-sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        // Включает XSS фильтр в браузере
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Управляет Referrer header
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Content Security Policy
        $response->headers->set('Content-Security-Policy', 
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com; " .
            "style-src 'self' 'unsafe-inline' fonts.googleapis.com; " .
            "img-src 'self' data: https: blob:; " .
            "font-src 'self' fonts.gstatic.com; " .
            "connect-src 'self'; " .
            "frame-ancestors 'none'; " .
            "form-action 'self';"
        );
        
        // Strict Transport Security (только для HTTPS)
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        
        // Permissions Policy
        $response->headers->set('Permissions-Policy', 
            'camera=(), microphone=(), geolocation=(self), interest-cohort=()'
        );
        
        return $response;
    }
}
