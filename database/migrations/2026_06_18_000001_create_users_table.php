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
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            
            // --- Roles y Estado ---
            $table->enum('role', ['passenger', 'driver', 'admin'])->default('passenger');
            $table->boolean('is_active')->default(true);
            $table->text('ban_reason')->nullable();
            $table->boolean('is_approved')->default(false);

            // --- Identidad y Contacto ---
            $table->string('phone_number')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('id_card_number')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->boolean('terms_accepted')->default(false);

            // --- Ubicación y Municipio de Residencia (Dato Estático) ---
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('municipality')->nullable();
            // Nota: municipality_id representa el municipio de residencia/domicilio del usuario, no el municipio en tránsito.
            $table->foreignId('municipality_id')->nullable()->comment('Municipio de residencia/domicilio del usuario')->constrained('municipalities')->onDelete('set null');

            // --- Fotos y Documentación ---
            $table->string('profile_photo_path')->nullable();
            $table->string('id_card_photo_path')->nullable();
            $table->string('biometric_photo_path')->nullable();
            $table->string('license_file')->nullable();
            $table->string('medical_certificate_file')->nullable();
            $table->string('rif_file')->nullable();
            $table->string('circulation_permit_file_path')->nullable();

            $table->enum('identity_status', ['unverified', 'pending', 'approved', 'verified', 'rejected'])->default('unverified');
            $table->text('identity_feedback')->nullable();

            // --- Snapshot Última Posición Conocida (Lectura rápida / Caché en BD) ---
            // Nota: La trazabilidad e histórico en vivo residen en Redis y en la tabla 'vehicle_locations'.
            $table->decimal('current_lat', 10, 7)->nullable()->comment('Última latitud GPS conocida (snapshot)');
            $table->decimal('current_lng', 10, 7)->nullable()->comment('Última longitud GPS conocida (snapshot)');

            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
