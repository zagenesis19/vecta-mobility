<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabla de Vehículos (La dejamos igual, funciona bien)
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->string('model');
            $table->string('plate');
            $table->string('color');
            $table->enum('type', ['car', 'bike']);
            $table->timestamps();
        });

        // 2. Tabla de Viajes (CORREGIDA para Fase 5)
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('passenger_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('cascade');
            
            // --- CAMBIO IMPORTANTE: Usamos texto para las direcciones ---
            $table->string('origin');       // Ej: "Plaza Bolívar"
            $table->string('destination');  // Ej: "Centro Comercial"
            // ------------------------------------------------------------

            $table->string('status')->default('pending'); // pending, accepted, in_progress, completed
            
            // Cambiamos 'fare' por 'price' para coincidir con tu Controlador
            $table->decimal('price', 8, 2)->nullable(); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
        Schema::dropIfExists('vehicles');
    }
};