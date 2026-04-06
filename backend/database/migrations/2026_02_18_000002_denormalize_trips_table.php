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
        Schema::table('trips', function (Blueprint $table) {
            // Desnormalización: Guardar datos "congelados" del momento del viaje
            // para que no cambien si el usuario edita su perfil después.
            $table->string('passenger_snapshot_name')->nullable()->after('passenger_id');
            $table->string('passenger_snapshot_phone')->nullable()->after('passenger_snapshot_name');
            
            $table->string('driver_snapshot_name')->nullable()->after('driver_id');
            $table->string('driver_snapshot_phone')->nullable()->after('driver_snapshot_name');
            $table->string('driver_snapshot_photo')->nullable()->after('driver_snapshot_phone'); // Ruta foto perfil
            
            // Datos del vehículo congelados (JSON o columnas planas, JSON es más flexible aquí)
            $table->json('vehicle_snapshot_data')->nullable()->after('vehicle_type'); 
            // Estructura esperada: {model, plate, year, color, type}
            
            // Motivo de rechazo por parte del conductor (Solicitud #1 y #4)
            // Ya teníamos cancellation_reason, agregamos rejection_reason para ser explícitos o usamos el mismo.
            // El usuario pidió "opcion del motivo... rechace una solicitud".
            // Usaremos una columna específica para claridad si es rechazo vs cancelación
            $table->string('rejection_reason')->nullable()->after('cancellation_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn([
                'passenger_snapshot_name', 
                'passenger_snapshot_phone',
                'driver_snapshot_name',
                'driver_snapshot_phone',
                'driver_snapshot_photo',
                'vehicle_snapshot_data',
                'rejection_reason'
            ]);
        });
    }
};
