<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Trip;
use App\Models\Municipality;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with complete test data.
     */
    public function run(): void
    {
        // 1. ADMINISTRADOR
        $admin = User::firstOrCreate(
            ['email' => 'admin@vecta.com'],
            [
                'name' => 'Admin Rapiditos',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'identity_status' => 'verified',
                'id_card_number' => 'V-28456123',
                'phone_number' => '04241234567',
            ]
        );

        // 2. PASAJERO PRINCIPAL
        $passenger = User::firstOrCreate(
            ['email' => 'zgenesisangelina@gmail.com'],
            [
                'name' => 'Génesis Zapata',
                'password' => Hash::make('neronero123'),
                'role' => 'passenger',
                'is_active' => true,
                'identity_status' => 'verified',
                'id_card_number' => 'V-31332083',
                'phone_number' => '04241928802',
            ]
        );

        // 3. CONDUCTOR 1 (Carro - Charallave)
        $driver1 = User::firstOrCreate(
            ['email' => 'juanavila@gmail.com'],
            [
                'name' => 'Juan Ávila',
                'password' => Hash::make('juanavila123'),
                'role' => 'driver',
                'is_active' => true,
                'is_approved' => true,
                'identity_status' => 'verified',
                'id_card_number' => 'V-26789456',
                'phone_number' => '04142567890',
                'municipality' => 'Charallave',
                'current_lat' => 10.2443,
                'current_lng' => -66.8622,
            ]
        );

        if (!$driver1->vehicle) {
            Vehicle::create([
                'user_id' => $driver1->id,
                'type' => 'car',
                'model' => 'Toyota Corolla',
                'plate' => 'ABC123',
                'year' => 2020,
                'color' => 'Blanco',
            ]);
        }

        // 4. CONDUCTOR 2 (Moto - Cúa)
        $driver2 = User::firstOrCreate(
            ['email' => 'antonioperez@gmail.com'],
            [
                'name' => 'Antonio Pérez',
                'password' => Hash::make('antonioperez123'),
                'role' => 'driver',
                'is_active' => true,
                'is_approved' => true,
                'identity_status' => 'verified',
                'id_card_number' => 'V-25123789',
                'phone_number' => '04126543210',
                'municipality' => 'Cúa',
                'current_lat' => 10.2450,
                'current_lng' => -66.8630,
            ]
        );

        if (!$driver2->vehicle) {
            Vehicle::create([
                'user_id' => $driver2->id,
                'type' => 'motorcycle',
                'model' => 'Yamaha FZ',
                'plate' => 'XYZ789',
                'year' => 2021,
                'color' => 'Negro',
            ]);
        }

        // 5. CONDUCTOR PENDIENTE / ADICIONAL (Carro - Yare)
        $driver3 = User::firstOrCreate(
            ['email' => 'jose.perez@gmail.com'],
            [
                'name' => 'José Pérez',
                'password' => Hash::make('password'),
                'role' => 'driver',
                'is_active' => true,
                'is_approved' => true,
                'identity_status' => 'verified',
                'id_card_number' => 'V-27456890',
                'phone_number' => '04145678901',
                'municipality' => 'San Francisco de Yare',
                'current_lat' => 10.2400,
                'current_lng' => -66.8600,
            ]
        );

        if (!$driver3->vehicle) {
            Vehicle::create([
                'user_id' => $driver3->id,
                'type' => 'car',
                'model' => 'Chevrolet Aveo',
                'plate' => 'DEF456',
                'year' => 2019,
                'color' => 'Rojo',
            ]);
        }

        // 6. GENERAR VIAJES DE HISTORIAL (Con reseñas registradas para no bloquear modales)
        if (Trip::count() === 0) {
            $drivers = [$driver1->id, $driver2->id, $driver3->id];
            
            // Viaje 1: Completado y ya calificado (Histórico)
            $completedTrip = Trip::create([
                'passenger_id' => $passenger->id,
                'driver_id' => $driver1->id,
                'origin_address' => 'Plaza Bolívar, Charallave',
                'destination_address' => 'Estación Ferrocarril Cúa',
                'origin_lat' => 10.2460,
                'origin_lng' => -66.8620,
                'destination_lat' => 10.1630,
                'destination_lng' => -66.8850,
                'status' => 'completed',
                'price' => 12.50,
                'payment_method' => 'Efectivo',
                'payment_confirmed' => true,
                'finished_at' => now()->subDays(1),
                'passenger_snapshot_name' => $passenger->name,
                'passenger_snapshot_phone' => $passenger->phone_number,
            ]);

            \App\Models\Review::create([
                'trip_id' => $completedTrip->id,
                'reviewer_id' => $passenger->id,
                'reviewed_id' => $driver1->id,
                'rating' => 5,
                'comment' => '¡Excelente servicio!'
            ]);
        }
    }
}
