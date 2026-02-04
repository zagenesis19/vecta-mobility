<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. ADMINISTRADOR
        User::create([
            'name' => 'Admin Vecta',
            'email' => 'admin@vecta.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_approved' => true,
            'identity_status' => 'verified',
            'id_card_number' => '12345678',
            'phone_number' => '04121234567',
        ]);

        // 2. CONDUCTOR (Carro)
        $driver1 = User::create([
            'name' => 'Juan Avila',
            'email' => 'juanavila@gmail.com',
            'password' => Hash::make('juanavila123'),
            'role' => 'driver',
            'is_approved' => true,
            'identity_status' => 'verified',
            'id_card_number' => '23456789',
            'phone_number' => '04142345678',
            'current_lat' => 10.2443,
            'current_lng' => -66.8622,
        ]);

        Vehicle::create([
            'user_id' => $driver1->id,
            'type' => 'car',
            'model' => 'Toyota Corolla',
            'plate' => 'ABC123',
            'year' => 2020,
            'color' => 'Blanco',
        ]);

        // 3. CONDUCTOR (Moto)
        $driver2 = User::create([
            'name' => 'Antonio Pérez',
            'email' => 'antonioperez@gmail.com',
            'password' => Hash::make('antonioperez123'),
            'role' => 'driver',
            'is_approved' => true,
            'identity_status' => 'verified',
            'id_card_number' => '34567890',
            'phone_number' => '04243456789',
            'current_lat' => 10.2450,
            'current_lng' => -66.8630,
        ]);

        Vehicle::create([
            'user_id' => $driver2->id,
            'type' => 'motorcycle',
            'model' => 'Yamaha FZ',
            'plate' => 'XYZ789',
            'year' => 2021,
            'color' => 'Negro',
        ]);

        // 4. PASAJERO
        User::create([
            'name' => 'Génesis Zapata',
            'email' => 'zgenesisangelina@gmail.com',
            'password' => Hash::make('neronero123'),
            'role' => 'passenger',
            'is_approved' => true,
            'identity_status' => 'verified',
            'id_card_number' => '31332083',
            'phone_number' => '04241928802',
        ]);

        // 5. CONDUCTOR PENDIENTE DE APROBACIÓN
        $driverPending = User::create([
            'name' => 'José Pérez',
            'email' => 'joseperez@gmail.com',
            'password' => Hash::make('joseperez123'),
            'role' => 'driver',
            'is_approved' => false,
            'identity_status' => 'pending',
            'id_card_number' => '56789012',
            'phone_number' => '04145678901',
        ]);

        Vehicle::create([
            'user_id' => $driverPending->id,
            'type' => 'car',
            'model' => 'Chevrolet Aveo',
            'plate' => 'DEF456',
            'year' => 2019,
            'color' => 'Rojo',
        ]);
    }
}
