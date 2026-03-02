<?php
// database/migrations/2024_01_01_000010_create_activity_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // created, updated, deleted, login, logout, failed_login
            $table->string('model_type')->nullable(); // App\Models\Event
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable(); // Доп. данные
            $table->timestamps();
            
            // Индексы для поиска
            $table->index(['model_type', 'model_id']);
            $table->index('action');
            $table->index('ip_address');
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('activity_logs');
    }
};
