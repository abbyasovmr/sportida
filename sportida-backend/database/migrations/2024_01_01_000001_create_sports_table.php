<?php
// database/migrations/2024_01_01_000001_create_sports_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->string('color', 7)->default('#6B4EFF');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('seo_data')->nullable();
            $table->timestamps();
            
            $table->index('slug');
            $table->index('is_active');
        });
    }

    public function down(): void {
        Schema::dropIfExists('sports');
    }
};
