<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla para el histórico de telemetría y trazabilidad GPS (Breadcrumb Trail).
     * Resuelve la falta de histórico al desvincular el registro de ubicaciones de la tabla 'users'.
     */
    public function up(): void
    {
        Schema::create('vehicle_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('trip_id')->nullable()->constrained('trips')->onDelete('cascade');
            
            // Coordenadas GPS
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            
            // Telemetría adicional
            $table->decimal('speed', 5, 2)->nullable(); // Velocidad en km/h
            $table->decimal('heading', 5, 2)->nullable(); // Dirección/Rumbo (0-360°)
            
            // Municipio de tránsito (dinámico por donde circula)
            $table->foreignId('municipality_id')->nullable()->constrained('municipalities')->onDelete('set null');
            
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            // Índices compuestos para consultas históricas rápidas y optimización de latencia
            $table->index(['driver_id', 'recorded_at']);
            $table->index(['trip_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_locations');
    }
};
