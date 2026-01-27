<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            
            // --- Roles y Aprobación ---
            $table->string('role')->default('passenger'); // 'admin', 'driver', 'passenger'
            $table->boolean('is_approved')->default(false);

            // --- Datos de Conductor (Vehículo) ---
            $table->string('vehicle_model')->nullable();
            $table->string('vehicle_plate')->nullable();
            $table->integer('vehicle_year')->nullable();
            $table->string('vehicle_color')->nullable();
            $table->string('license_file')->nullable();


            // --- NUEVOS: Identidad y Seguridad (Fase 5) ---
            $table->string('phone_number')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            
            // DOCUMENTO DE IDENTIDAD
            $table->string('id_card_number')->nullable();
            $table->date('birth_date')->nullable();           // <--- NUEVO (Para comparar)
            $table->date('id_card_expires_at')->nullable();
            $table->string('id_card_photo_path')->nullable();
            
            // ESTADO DE VERIFICACIÓN
            $table->string('identity_status')->default('unverified'); // 'unverified', 'pending', 'approved', 'rejected'
            $table->string('identity_feedback')->nullable(); // Mensaje del admin si rechaza
            
            // FOTOS
            $table->string('profile_photo_path')->nullable();
            $table->string('biometric_photo_path')->nullable();

            // ...
            $table->rememberToken();
            $table->timestamps();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};