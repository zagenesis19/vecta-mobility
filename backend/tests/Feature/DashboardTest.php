<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase; // Limpia la BD después de cada test

    public function test_guests_are_redirected_to_login()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_passenger_can_view_dashboard()
    {
        $user = User::factory()->create([
            'role' => 'passenger',
        ]);

        // Crear un viaje para este pasajero
        Trip::factory()->create([
            'passenger_id' => $user->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('userRole', 'passenger')
            ->has('trips') // Debe tener historial
            ->has('currentTrip') // Debe tener viaje actual (es el pending que creamos)
        );
    }

    public function test_driver_can_view_dashboard()
    {
        $user = User::factory()->create([
            'role' => 'driver',
            'is_approved' => true
        ]);

        // Un viaje disponible (otro pasajero)
        Trip::factory()->create([
            'status' => 'pending',
            'driver_id' => null,
            'vehicle_type' => 'car'
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('userRole', 'driver')
            ->where('isApproved', true)
            ->has('availableTrips', 1) // Debe ver 1 viaje disponible
        );
    }

    public function test_admin_can_view_dashboard()
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('userRole', 'admin')
            ->has('trips') // Lista global
            ->has('driverLocations') // Mapa de calor
        );
    }
}
