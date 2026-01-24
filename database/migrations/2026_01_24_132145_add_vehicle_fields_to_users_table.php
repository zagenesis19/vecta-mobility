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
        Schema::table('users', function (Blueprint $table) {
            // Campos para el Vehículo del Conductor
            $table->string('car_model')->nullable();      // Ej: Toyota Corolla
            $table->string('license_plate')->nullable();  // Ej: ABC-123
            $table->integer('vehicle_year')->nullable();  // Ej: 2015
            
            // Campo para la foto de la licencia (guardaremos la ruta del archivo)
            $table->string('license_photo_path')->nullable(); 

            // Estado de Aprobación (Por defecto es FALSE hasta que tú lo apruebes)
            $table->boolean('is_approved')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'car_model', 
                'license_plate', 
                'vehicle_year', 
                'license_photo_path', 
                'is_approved'
            ]);
        });
    }
};
