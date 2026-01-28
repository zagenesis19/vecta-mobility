<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            
            // RELACIONES
            $table->foreignId('passenger_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null');
            
            // RUTA (Texto para mostrar + Coordenadas para el mapa)
            $table->string('origin_address'); // Dirección legible (Ej. "Centro Comercial")
            $table->string('destination_address');
            
            $table->decimal('origin_lat', 10, 8);
            $table->decimal('origin_lng', 11, 8);
            $table->decimal('destination_lat', 10, 8);
            $table->decimal('destination_lng', 11, 8);

            // ESTADO Y DINERO
            // pending: nadie lo ha tomado | accepted: chofer asignado | in_progress: rodando | completed: finalizado
            $table->string('status')->default('pending')->index(); 
            $table->decimal('price', 10, 2);
            $table->string('payment_method')->default('Efectivo'); // Efectivo, Pago Movil

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};