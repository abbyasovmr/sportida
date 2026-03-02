# Sportida Backend - Laravel + Filament

## Требования
- PHP 8.2+
- PostgreSQL 14+
- Redis 7+
- Node.js 18+

## Установка

```bash
composer create-project laravel/laravel sportida-backend
cd sportida-backend

# Установка Filament
composer require filament/filament:"^3.0" -W

# Установка Spatie Permission (роли)
composer require spatie/laravel-permission

# Установка пакетов для изображений
composer require intervention/image
composer require spatie/laravel-image-optimizer

# Установка Filament Shield (управление правами)
composer require bezhansalleh/filament-shield

php artisan filament:install --panels
php artisan shield:install
```

## Структура ролей

1. **super_admin** - Полный доступ, настройка системы
2. **admin** - Управление контентом, модерация
3. **editor** - Создание/редактирование статей и мероприятий
4. **organizer** - Управление своими мероприятиями и заявками
5. **trainer** - Просмотр, подача заявок от своих спортсменов
6. **parent** - Просмотр, подача заявок за детей
7. **athlete** - Просмотр, подача заявок на себя

## Настройка

```bash
# Миграции
php artisan migrate

# Создание супер-админа
php artisan shield:super-admin

# Сидеры
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=SportSeeder
php artisan db:seed --class=EventSeeder  # Данные с RG4U
```

## ИИ-оптимизация изображений

В админке автоматически:
- WebP конвертация
- Resize до нужных размеров
- Сжатие без потерь
- Генерация srcset

## API для фронтенда

```php
// routes/api.php
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{slug}', [EventController::class, 'show']);
Route::get('/organizations', [OrganizationController::class, 'index']);
Route::get('/news', [NewsController::class, 'index']);
```

## SEO

- Все страницы с SSR (Blade + Inertia)
- Sitemap自动生成
- Meta-теги из админки
- Open Graph для каждой страницы
