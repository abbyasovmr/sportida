<?php
// app/Services/AuditService.php

namespace App\Services;

use App\Models\ActivityLog;

class AuditService
{
    public static function log(
        string $action,
        $model = null,
        array $oldValues = [],
        array $newValues = [],
        array $metadata = []
    ): void {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'old_values' => !empty($oldValues) ? $oldValues : null,
            'new_values' => !empty($newValues) ? $newValues : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => !empty($metadata) ? $metadata : null,
        ]);
    }

    public static function logLogin(): void
    {
        self::log('login', metadata: ['time' => now()->toDateTimeString()]);
    }

    public static function logLogout(): void
    {
        self::log('logout');
    }

    public static function logFailedLogin(string $email): void
    {
        self::log('failed_login', metadata: [
            'email' => $email,
            'ip' => request()->ip(),
            'severity' => 'high',
        ]);
    }

    public static function logPasswordReset(): void
    {
        self::log('password_reset', metadata: ['severity' => 'high']);
    }

    public static function logModelCreated($model): void
    {
        self::log('created', $model, [], $model->toArray());
    }

    public static function logModelUpdated($model, array $oldValues): void
    {
        $changes = $model->getChanges();
        unset($changes['updated_at']);
        
        self::log('updated', $model, $oldValues, $changes);
    }

    public static function logModelDeleted($model): void
    {
        self::log('deleted', $model, $model->toArray(), []);
    }

    public static function logUnauthorizedAccess(string $resource): void
    {
        self::log('unauthorized_access', metadata: [
            'resource' => $resource,
            'ip' => request()->ip(),
            'severity' => 'high',
        ]);
    }

    public static function logSuspiciousActivity(string $description, array $metadata = []): void
    {
        self::log('suspicious_activity', metadata: array_merge([
            'description' => $description,
            'severity' => 'high',
        ], $metadata));
    }
}
