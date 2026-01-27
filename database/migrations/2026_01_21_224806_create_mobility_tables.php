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
        // 1. TABLA DE VIAJES (TRIPS)
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('passenger_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Direcciones (Texto)
            $table->string('origin');
            $table->string('destination');

            // 🔥 COORDENADAS GPS (Nuevas columnas)
            $table->decimal('origin_lat', 11, 8)->nullable();
            $table->decimal('origin_lng', 11, 8)->nullable();
            $table->decimal('destination_lat', 11, 8)->nullable();
            $table->decimal('destination_lng', 11, 8)->nullable();

            // Detalles del viaje
            $table->string('status')->default('pending'); // pending, accepted, in_progress, completed
            $table->decimal('price', 10, 2);
            
            // 🔥 MÉTODO DE PAGO (Nueva columna)
            $table->string('payment_method')->default('Efectivo'); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};