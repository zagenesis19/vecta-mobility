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
        $passenger = User::factory();

        return [
            'passenger_id' => $passenger,
            'origin_address' => 'Plaza Bolívar, Charallave',
            'destination_address' => 'Estación Ferrocarril Cúa',
            'origin_lat' => 10.2460,
            'origin_lng' => -66.8620,
            'destination_lat' => 10.1630,
            'destination_lng' => -66.8850,
            'status' => 'pending',
            'price' => $this->faker->randomFloat(2, 5, 50),
            'vehicle_type' => 'car',
            'payment_method' => 'Efectivo',
            'passenger_snapshot_name' => $this->faker->name(),
            'passenger_snapshot_phone' => '0414' . $this->faker->numerify('#######'),
        ];
    }

    /**
     * Estado para viajes aceptados.
     */
    public function accepted(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'accepted',
                'driver_id' => User::factory()->driver(),
                'accepted_at' => now(),
                'driver_snapshot_name' => $this->faker->name(),
                'driver_snapshot_phone' => '0412' . $this->faker->numerify('#######'),
            ];
        });
    }

    /**
     * Estado para viajes en curso.
     */
    public function inProgress(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'in_progress',
                'driver_id' => User::factory()->driver(),
                'accepted_at' => now()->subMinutes(15),
                'started_at' => now()->subMinutes(10),
                'driver_snapshot_name' => $this->faker->name(),
                'driver_snapshot_phone' => '0412' . $this->faker->numerify('#######'),
            ];
        });
    }

    /**
     * Estado para viajes completados.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'driver_id' => User::factory()->driver(),
                'accepted_at' => now()->subMinutes(45),
                'started_at' => now()->subMinutes(30),
                'finished_at' => now(),
                'duration_minutes' => 30,
                'payment_confirmed' => true,
                'driver_snapshot_name' => $this->faker->name(),
                'driver_snapshot_phone' => '0412' . $this->faker->numerify('#######'),
            ];
        });
    }

    /**
     * Estado para viajes cancelados.
     */
    public function cancelled(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'cancelled',
                'cancelled_by' => 'passenger',
                'cancellation_reason' => 'Cambié de opinión',
                'cancelled_at' => now(),
            ];
        });
    }
}
