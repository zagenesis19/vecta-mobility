<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'passenger',
            'is_active' => true,
            'phone_number' => '0414' . fake()->numerify('#######'),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return $this
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Estado para conductores aprobados con vehículo.
     */
    public function driver(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'driver',
            'is_approved' => true,
            'identity_status' => 'verified',
            'current_lat' => 10.2443 + (rand(-100, 100) / 10000),
            'current_lng' => -66.8622 + (rand(-100, 100) / 10000),
        ]);
    }

    /**
     * Estado para administradores.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
