# Аудит информационной безопасности Sportida

## Дата аудита: 2026-03-01
## Система: Sportida Backend (Laravel + Filament)

---

## 1. Аутентификация и авторизация

### Текущее состояние
- Используется Laravel Jetstream/Fortify (предполагается)
- Spatie Laravel Permission для ролей
- Filament Shield для доступа к админке

### Риски
- ❌ Нет двухфакторной аутентификации (2FA) для админов
- ❌ Нет ограничения на попытки входа (brute force)
- ❌ Нет проверки сложности пароля по умолчанию
- ❌ Нет принудительной смены пароля при первом входе
- ❌ Нет инвалидации сессий при смене пароля

### Рекомендации

```php
// config/fortify.php
'features' => [
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]),
    Features::updatePasswords(),
],

'passwords' => [
    'min' => 12, // Минимум 12 символов
    'mixedCase' => true,
    'letters' => true,
    'numbers' => true,
    'symbols' => true,
    'uncompromised' => true, // Проверка в Have I Been Pwned
],
```

```php
// Middleware для ограничения попыток входа
Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware(['throttle:5,1']); // 5 попыток в минуту
```

---

## 2. Защита API

### Текущее состояние
- API endpoints существуют (определены в документации)

### Риски
- ❌ Нет rate limiting на API
- ❌ Нет CSRF-защиты для stateless API
- ❌ Нет API versioning
- ❌ Нет CORS-ограничений

### Рекомендации

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Защищенные endpoints
});

Route::middleware(['throttle:30,1'])->group(function () {
    // Публичные endpoints с ограничением
});
```

```php
// config/cors.php
'allowed_origins' => [
    'https://sportida.ru',
    'https://admin.sportida.ru',
], // Только production домены
```

---

## 3. Защита данных (SQL, XSS, CSRF)

### Текущее состояние
- Используются Eloquent ORM (защита от SQL-инъекций)
- Blade escaping (защита от XSS)

### Риски
- ⚠️ Нет валидации входных данных в парсере
- ⚠️ Нет проверки mime-type загружаемых файлов
- ❌ Нет Content Security Policy (CSP)
- ❌ Нет X-Frame-Options заголовков

### Рекомендации

```php
// Content Security Policy middleware
class SecurityHeaders
{
    public function handle($request, $next)
    {
        $response = $next($request);
        
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Content-Security-Policy', 
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; " .
            "style-src 'self' 'unsafe-inline' fonts.googleapis.com; " .
            "img-src 'self' data: https:; " .
            "font-src 'self' fonts.gstatic.com;"
        );
        
        return $response;
    }
}
```

---

## 4. Защита загрузок файлов

### Текущее состояние
- FileUpload в Filament
- Оптимизация изображений через Intervention

### Риски
- ⚠️ Нет сканирования на вирусы
- ⚠️ Нет ограничения размера файлов
- ❌ Хранение файлов в public (можно угадать URL)
- ❌ Нет проверки расширения файла

### Рекомендации

```php
// AppServiceProvider.php
Validator::extend('safe_image', function ($attribute, $value) {
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $value->path());
    return in_array($mime, $allowedMimeTypes);
});

// В Filament Resource
FileUpload::make('cover_image')
    ->rules(['required', 'safe_image', 'max:5120']) // 5MB max
    ->disk('private') // Не public!
    ->directory('events/covers')
    ->visibility('private');
```

```php
// Роут для безопасной выдачи файлов
Route::get('/files/{path}', function ($path) {
    if (!Storage::disk('private')->exists($path)) {
        abort(404);
    }
    
    // Проверка прав доступа
    if (!auth()->user()->can('view files')) {
        abort(403);
    }
    
    return Storage::disk('private')->response($path);
})->where('path', '.*');
```

---

## 5. Rate Limiting и DDoS защита

### Текущее состояние
- Нет явного rate limiting

### Риски
- ❌ Уязвимость к brute force атакам
- ❌ Уязвимость к спаму форм
- ❌ Перегрузка API

### Рекомендации

```php
// app/Providers/RouteServiceProvider.php
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});

RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('registration', function (Request $request) {
    return Limit::perHour(10)->by($request->ip());
});
```

---

## 6. Логирование и мониторинг

### Текущее состояние
- Стандартное Laravel логирование

### Риски
- ❌ Нет логирования входов/выходов
- ❌ Нет логирования изменений важных данных
- ❌ Нет алертов на подозрительную активность

### Рекомендации

```php
// Сервис для аудита
class AuditService
{
    public static function log(string $action, $model = null, array $meta = [])
    {
        Activity::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $meta,
        ]);
    }
}

// В моделях
protected static function booted()
{
    static::created(fn ($model) => AuditService::log('created', $model));
    static::updated(fn ($model) => AuditService::log('updated', $model, ['changes' => $model->getChanges()]));
    static::deleted(fn ($model) => AuditService::log('deleted', $model));
}
```

---

## 7. Безопасность базы данных

### Риски
- ❌ Нет шифрования sensitive данных (email, телефон)
- ❌ Нет автоматических бэкапов
- ❌ Все пользователи под одним DB user

### Рекомендации

```php
// Шифрование в модели
use Illuminate\Database\Eloquent\Casts\Attribute;

protected function email(): Attribute
{
    return Attribute::make(
        get: fn ($value) => decrypt($value),
        set: fn ($value) => encrypt($value),
    );
}
```

```bash
# Автоматические бэкапы (в cron)
0 2 * * * pg_dump -U sportida sportida_db | gzip > /backup/sportida_$(date +\%Y\%m\%d).sql.gz
# Хранить 30 дней
find /backup -name "sportida_*.sql.gz" -mtime +30 -delete
```

---

## 8. Безопасность админки (Filament)

### Риски
- ⚠️ Доступ к админке по одному URL
- ⚠️ Нет IP whitelist для админов
- ❌ Нет timeout сессий
- ❌ Нет принудительного выхода при бездействии

### Рекомендации

```php
// config/session.php
'lifetime' => 120, // 2 часа
'expire_on_close' => false,
'same_site' => 'strict',
```

```php
// Middleware для админки
class AdminSecurity
{
    public function handle($request, $next)
    {
        // IP Whitelist
        $allowedIps = config('admin.allowed_ips', []);
        if (!empty($allowedIps) && !in_array($request->ip(), $allowedIps)) {
            abort(403, 'Access denied from this IP');
        }
        
        // 2FA проверка для админов
        if (auth()->user()->hasRole('super_admin') && !session('2fa_verified')) {
            return redirect()->route('2fa.verify');
        }
        
        return $next($request);
    }
}
```

---

## 9. Защита от ботов

### Рекомендации
- reCAPTCHA v3 на формах регистрации и заявок
- Honeypot поля в формах
- Задержка между действиями

```php
// Registration form
Forms\Components\TextInput::make('website') // Honeypot
    ->hidden()
    ->dehydrated(false)
    ->extraAttributes(['tabindex' => '-1']),

Forms\Components\Captcha::make('captcha') // reCAPTCHA
    ->required(),
```

---

## 10. План внедрения (приоритеты)

### Критично (неделя 1)
1. Включить rate limiting на login/API
2. Добавить password policy
3. CSP заголовки
4. Защита загрузок (проверка mime-type)

### Высокий (неделя 2-3)
5. 2FA для админов
6. Audit logging
7. Шифрование sensitive данных
8. Бэкапы

### Средний (месяц 1)
9. IP whitelist для админки
10. reCAPTCHA
11. File storage в private disk
12. Автоматические security headers

### Низкий (месяц 2-3)
13. Security scanning CI/CD
14. Penetration testing
15. Bug bounty программа

---

## Контрольный список перед production

- [ ] Rate limiting включен
- [ ] HTTPS only (HSTS enabled)
- [ ] Security headers установлены
- [ ] DB credentials в .env (не в коде)
- [ ] APP_DEBUG=false
- [ ] APP_ENV=production
- [ ] PHP expose_php=off
- [ ] Error logging настроен (не показывать пользователям)
- [ ] Session secure cookie
- [ ] Firewall настроен (только 80/443)
- [ ] fail2ban для SSH
- [ ] Регулярные обновления composer packages

---

## Рекомендуемые инструменты

1. **Laravel Security Checker** - `enlightn/enlightn`
2. **Dependency check** - `sensiolabs/security-checker`
3. **Static analysis** - `phpstan/phpstan`
4. **WAF** - Cloudflare или AWS WAF
5. **Monitoring** - Sentry для ошибок, Laravel Telescope для dev
6. **Secrets management** - Laravel Vapor или HashiCorp Vault

---

## Оценка рисков

| Риск | Уровень | Влияние | Статус |
|------|---------|---------|--------|
| Brute force | 🔴 Высокий | Критичный | Не исправлено |
| XSS | 🟡 Средний | Высокий | Частично |
| File upload | 🔴 Высокий | Критичный | Не исправлено |
| SQL Injection | 🟢 Низкий | Критичный | Исправлено (ORM) |
| Data leak | 🟡 Средний | Высокий | Не исправлено |
| DDoS | 🔴 Высокий | Высокий | Не исправлено |

**Общая оценка безопасности: 4/10** (Требуется немедленное вмешательство)
