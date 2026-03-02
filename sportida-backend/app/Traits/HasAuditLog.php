<?php
// app/Traits/HasAuditLog.php

namespace App\Traits;

use App\Services\AuditService;

trait HasAuditLog
{
    public static function bootHasAuditLog(): void
    {
        static::created(function ($model) {
            AuditService::logModelCreated($model);
        });

        static::updating(function ($model) {
            // Сохраняем старые значения перед обновлением
            $model->oldValues = $model->getOriginal();
        });

        static::updated(function ($model) {
            if (!empty($model->oldValues)) {
                AuditService::logModelUpdated($model, $model->oldValues);
                $model->oldValues = null;
            }
        });

        static::deleted(function ($model) {
            AuditService::logModelDeleted($model);
        });

        static::restored(function ($model) {
            AuditService::log('restored', $model);
        });
    }
}
