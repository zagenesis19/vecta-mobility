<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de Vehículos
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->string('model');
            $table->string('plate');
            $table->string('color');
            $table->enum('type', ['car', 'bike']);
            $table->timestamps();
        });

        // Tabla de Viajes
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('passenger_id')->constrained('users'); // El usuario que pide el viaje
            $table->foreignId('driver_id')->nullable()->constrained('users'); // El conductor (puede ser nulo al inicio)
            $table->decimal('origin_lat', 10, 8);
            $table->decimal('origin_long', 11, 8);
            $table->decimal('dest_lat', 10, 8);
            $table->decimal('dest_long', 11, 8);
            $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])->default('pending');
            $table->decimal('fare', 8, 2); // Precio
            $table->double('distance'); // Distancia
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
        Schema::dropIfExists('vehicles');
    }
};