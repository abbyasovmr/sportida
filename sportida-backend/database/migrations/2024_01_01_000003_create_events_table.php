<?php
// database/migrations/2024_01_01_000003_create_events_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained();
            
            // Основное
            $table->enum('type', ['competition', 'championship', 'cup', 'training_camp', 'master_class', 'exhibition']);
            $table->enum('status', ['draft', 'published', 'registration_open', 'registration_closed', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_name', 100)->nullable();
            $table->longText('description')->nullable();
            $table->longText('program')->nullable();
            $table->longText('rules')->nullable();
            
            // Даты
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamp('registration_start')->nullable();
            $table->timestamp('registration_end')->nullable();
            
            // Место
            $table->string('city');
            $table->string('venue_name')->nullable();
            $table->text('venue_address')->nullable();
            $table->point('venue_coordinates')->nullable();
            
            // Медиа
            $table->string('cover_image')->nullable();
            $table->json('gallery')->nullable();
            $table->json('documents')->nullable(); // PDF положения
            
            // Участники
            $table->integer('max_participants')->nullable();
            $table->integer('current_participants')->default(0);
            
            // Оплата
            $table->decimal('price_min', 10, 2)->nullable();
            $table->decimal('price_max', 10, 2)->nullable();
            $table->enum('currency', ['RUB'])->default('RUB');
            
            // Настройки
            $table->boolean('is_classificational')->default(false);
            $table->json('judge_panel')->nullable();
            $table->json('metadata')->nullable();
            
            // Статистика
            $table->unsignedBigInteger('views_count')->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('published_at')->nullable();
            
            // SEO
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_keywords')->nullable();
            $table->string('og_image')->nullable();
            
            $table->timestamps();
            
            // Индексы
            $table->index('slug');
            $table->index('status');
            $table->index('type');
            $table->index('city');
            $table->index(['start_date', 'end_date']);
            $table->index(['status', 'start_date']);
            $table->fullText(['name', 'description']); // Поиск
        });
    }

    public function down(): void {
        Schema::dropIfExists('events');
    }
};
