<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trip>
 */
class TripFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'passenger_id' => User::factory(), // Crea un usuario si no se pasa
            'origin_address' => $this->faker->address,
            'destination_address' => $this->faker->address,
            'origin_lat' => 10.4806, // Caracas aprox
            'origin_lng' => -66.9036,
            'destination_lat' => 10.5000,
            'destination_lng' => -66.9500,
            'status' => 'pending',
            'price' => $this->faker->randomFloat(2, 5, 50),
            'vehicle_type' => 'car',
            'payment_method' => 'Efectivo',
        ];
    }

    /**
     * Estado para viajes completados.
     */
    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'driver_id' => User::factory()->state(['role' => 'driver']),
                'started_at' => now()->subMinutes(30),
                'finished_at' => now(),
            ];
        });
    }

    /**
     * Estado para viajes en curso.
     */
    public function inProgress()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'in_progress',
                'driver_id' => User::factory()->state(['role' => 'driver']),
                'started_at' => now()->subMinutes(10),
            ];
        });
    }
}
