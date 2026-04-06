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
        // 1. ADMIN
        if (!User::where('email', 'admin@vecta.com')->exists()) {
            User::create([
                'name' => 'Génesis Zapata',
                'email' => 'admin@vecta.com',
                'password' => Hash::make('password'),
                'role' => 'admin', // 🔥 Importante
                'is_approved' => true,
            ]);
        }

        // 2. CREAR 10 CONDUCTORES
        for ($i = 1; $i <= 10; $i++) {
            $driver = User::create([
                'name' => "Conductor $i",
                'email' => "conductor$i@test.com",
                'password' => Hash::make('password'),
                'role' => 'driver', // 🔥 Rol correcto
                'is_approved' => true, // 🔥 Aprobado para poder trabajar
            ]);

            Vehicle::create([
                'user_id' => $driver->id, // ✅ CORREGIDO: Antes decía driver_id
                'model' => 'Toyota Corolla',
                'plate' => 'ABC' . rand(100, 999),
                'color' => 'Blanco',
                'type' => 'car',
            ]);
        }

        // 3. CREAR UN PASAJERO
        $passenger = User::create([
            'name' => 'Pasajero Frecuente',
            'email' => 'cliente1@test.com',
            'password' => Hash::make('password'),
            'role' => 'passenger', // 🔥 Rol correcto
        ]);

        // 4. GENERAR 20 VIAJES (Con nombres de columnas corregidos)
        for ($i = 0; $i < 20; $i++) {
            Trip::create([
                'passenger_id' => $passenger->id,
                'driver_id' => rand(2, 11), // IDs de los conductores creados arriba
                
                // Direcciones ficticias
                'origin_address' => 'Plaza Bolívar, Charallave',
                'destination_address' => 'Estación Ferrocarril',
                
                // Coordenadas (Usando nombres estándar del controlador)
                'origin_lat' => 10.2460 + (rand(-100, 100) / 10000),
                'origin_lng' => -66.8620 + (rand(-100, 100) / 10000), // ✅ lng, no long
                'destination_lat' => 10.1630,
                'destination_lng' => -66.8850, // ✅ destination_lng
                
                'status' => ['completed', 'accepted', 'cancelled'][rand(0, 2)],
                'price' => rand(5, 20), // ✅ price, no fare
                'payment_method' => 'Efectivo',
            ]);
        }
    }
}