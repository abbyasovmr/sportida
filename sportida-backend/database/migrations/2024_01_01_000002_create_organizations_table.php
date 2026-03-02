<?php
// database/migrations/2024_01_01_000002_create_organizations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->enum('type', ['federation', 'regional_center', 'club', 'school', 'section']);
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_name', 100)->nullable();
            $table->string('legal_name')->nullable();
            $table->string('inn', 12)->nullable();
            $table->string('ogrn', 13)->nullable();
            $table->string('city');
            $table->string('region')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('logo')->nullable();
            $table->string('cover_image')->nullable();
            $table->text('description')->nullable();
            $table->json('requisites')->nullable();
            $table->foreignId('owner_id')->constrained('users');
            $table->enum('status', ['active', 'pending', 'blocked'])->default('pending');
            $table->boolean('is_verified')->default(false);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_keywords')->nullable();
            $table->timestamps();
            
            $table->index('slug');
            $table->index('type');
            $table->index('status');
            $table->index('city');
            $table->index(['type', 'status']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('organizations');
    }
};
