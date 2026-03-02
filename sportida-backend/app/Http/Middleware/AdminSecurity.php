<?php
// app/Http/Middleware/AdminSecurity.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminSecurity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        // Проверка аутентификации
        if (!$user) {
            abort(403, 'Unauthorized');
        }
        
        // IP Whitelist для super_admin
        if ($user->hasRole('super_admin')) {
            $allowedIps = config('admin.allowed_ips', []);
            
            if (!empty($allowedIps) && !in_array($request->ip(), $allowedIps)) {
                \Log::warning('Admin access denied from IP', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'url' => $request->url(),
                ]);
                abort(403, 'Access denied from this IP address');
            }
        }
        
        // Проверка 2FA для критичных ролей
        if (in_array($user->getRoleNames()->first(), ['super_admin', 'admin'])) {
            if (!$user->two_factor_confirmed_at && config('admin.require_2fa')) {
                return redirect()->route('profile.show')
                    ->with('error', 'Для доступа к админке необходимо включить двухфакторную аутентификацию');
            }
        }
        
        // Логирование доступа к админке
        \Log::info('Admin panel access', [
            'user_id' => $user->id,
            'role' => $user->getRoleNames()->first(),
            'ip' => $request->ip(),
            'url' => $request->url(),
            'method' => $request->method(),
        ]);
        
        return $next($request);
    }
}
