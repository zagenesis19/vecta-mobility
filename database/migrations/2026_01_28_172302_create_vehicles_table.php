<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            
            // Relación: El vehículo pertenece a un Usuario (Conductor)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Tipo: Aquí diferenciamos 'car' (Carro) de 'motorcycle' (Moto)
            $table->string('type')->default('car'); 
            
            // Datos del Vehículo (Nombres limpios sin prefijo 'vehicle_')
            $table->string('model')->nullable(); // Antes vehicle_model
            $table->string('plate')->nullable(); // Antes vehicle_plate
            $table->integer('year')->nullable(); // Antes vehicle_year
            $table->string('color')->nullable(); // Antes vehicle_color
            
            // Extra: Foto del vehículo (Recomendado para seguridad)
            $table->string('photo_path')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};