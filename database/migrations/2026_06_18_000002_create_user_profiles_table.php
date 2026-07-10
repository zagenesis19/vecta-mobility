<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // --- Contacto ---
            $table->string('phone_number')->unique()->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            
            // --- Identidad ---
            $table->string('id_card_number')->nullable();
            $table->date('birth_date')->nullable();
            $table->date('id_card_expires_at')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            
            // --- Fotos y Verificación ---
            $table->string('profile_photo_path')->nullable();
            $table->string('id_card_photo_path')->nullable();
            $table->string('biometric_photo_path')->nullable();
            
            $table->enum('identity_status', ['unverified', 'pending', 'verified', 'rejected'])->default('unverified');
            $table->text('identity_feedback')->nullable();
            
            // --- Legales ---
            $table->boolean('terms_accepted')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
