<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            
            // === AGREGAMOS TODO AQUÍ DIRECTAMENTE ===
            $table->string('role')->default('passenger'); // passenger, driver, admin
            
            // Campos exclusivos del Conductor
            $table->boolean('is_approved')->default(false);
            $table->string('vehicle_model')->nullable();
            $table->string('vehicle_plate')->nullable();
            $table->integer('vehicle_year')->nullable();
            $table->string('vehicle_color')->nullable();
            $table->string('license_file')->nullable();
            // ========================================

            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};