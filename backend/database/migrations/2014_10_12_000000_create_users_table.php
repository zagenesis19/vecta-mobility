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
            
            // --- Roles y Aprobación ---
            $table->string('role')->default('passenger'); 
            $table->boolean('is_approved')->default(false);

            // --- ✂️ AQUÍ ESTÁ EL CAMBIO ---
            // Quitamos los datos del vehículo (se mueven a la otra tabla)
            // MANTENEMOS la licencia aquí porque pertenece al conductor
            $table->string('license_file')->nullable(); 

            // --- Identidad y Seguridad (Fase 5 - Tus nombres originales) ---
            $table->string('phone_number')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            
            // DOCUMENTO DE IDENTIDAD
            $table->string('id_card_number')->nullable(); // Tu nombre original
            $table->date('birth_date')->nullable();
            $table->date('id_card_expires_at')->nullable();
            $table->string('id_card_photo_path')->nullable();
            
            // ESTADO DE VERIFICACIÓN
            $table->string('identity_status')->default('unverified'); 
            $table->string('identity_feedback')->nullable(); 
            
            // FOTOS
            $table->string('profile_photo_path')->nullable();
            $table->string('biometric_photo_path')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};