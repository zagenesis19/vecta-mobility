<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Trip;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Verificamos si ya existe el admin para no duplicarlo
        if (!User::where('email', 'admin@vecta.com')->exists()) {
            User::create([
                'name' => 'Génesis Zapata',
                'email' => 'admin@vecta.com',
                'password' => Hash::make('password'),
            ]);
        }

        // 2. Crear 10 Conductores
        for ($i = 1; $i <= 10; $i++) {
            $driver = User::create([
                'name' => "Conductor $i",
                'email' => "conductor$i@test.com", // Cambié el email para evitar conflictos
                'password' => Hash::make('password'),
            ]);

            Vehicle::create([
                'driver_id' => $driver->id,
                'model' => 'Toyota Corolla',
                'plate' => 'ABC' . rand(100, 999),
                'color' => 'Blanco',
                'type' => 'car',
            ]);
        }

        // 3. Crear un Pasajero
        $passenger = User::create([
            'name' => 'Pasajero Frecuente',
            'email' => 'cliente' . rand(1,999) . '@test.com', // Email único
            'password' => Hash::make('password'),
        ]);

        // 4. GENERAR 20 VIAJES (Esto es lo que te falta)
        for ($i = 0; $i < 20; $i++) {
            Trip::create([
                'passenger_id' => $passenger->id,
                'driver_id' => rand(2, 11), // IDs aproximados de los conductores
                'origin_lat' => 10.2460,
                'origin_long' => -66.8620,
                'dest_lat' => 10.1630,
                'dest_long' => -66.8850,
                'status' => ['completed', 'active', 'cancelled'][rand(0, 2)],
                'fare' => rand(5, 20),
                'distance' => rand(5, 15),
            ]);
        }
    }
}