<?php
// app/Providers/RouteServiceProvider.php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/dashboard';

    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    protected function configureRateLimiting(): void
    {
        // API общий лимит
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // API публичный (для сайта)
        RateLimiter::for('api-public', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // Логин - строгий лимит
        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(3)->by($request->input('email') ?: $request->ip()),
            ];
        });

        // Регистрация
        RateLimiter::for('registration', function (Request $request) {
            return Limit::perHour(10)->by($request->ip());
        });

        // Формы заявок
        RateLimiter::for('applications', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Загрузка файлов
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        // Поиск (частый)
        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        // Admin panel - более строгий
        RateLimiter::for('admin', function (Request $request) {
            return Limit::perMinute(100)->by($request->user()?->id ?: $request->ip());
        });
    }
}
