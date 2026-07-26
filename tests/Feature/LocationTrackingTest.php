<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Trip;
use App\Models\VehicleLocation;
use App\Jobs\ProcessLocationUpdateJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Test de Telemetría GPS y ProcessLocationUpdateJob.
 * Cubre: validación de coordenadas, caché, job dispatch, y persistencia.
 */
class LocationTrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $driver;
    private User $passenger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driver = User::factory()->driver()->create();
        $this->passenger = User::factory()->create(['role' => 'passenger']);
    }

    // =============================================
    // VALIDACIÓN DE COORDENADAS
    // =============================================

    public function test_rejects_coordinates_out_of_range()
    {
        $response = $this->actingAs($this->driver)->postJson('/driver/location', [
            'lat' => 999.0,
            'lng' => -999.0,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['lat', 'lng']);
    }

    public function test_rejects_missing_coordinates()
    {
        $response = $this->actingAs($this->driver)->postJson('/driver/location', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['lat', 'lng']);
    }

    public function test_accepts_valid_coordinates()
    {
        $response = $this->actingAs($this->driver)->postJson('/driver/location', [
            'lat' => 10.2443,
            'lng' => -66.8622,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ubicación actualizada', 'cached' => true]);
    }

    public function test_rejects_negative_speed()
    {
        $response = $this->actingAs($this->driver)->postJson('/driver/location', [
            'lat' => 10.2443,
            'lng' => -66.8622,
            'speed' => -50,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['speed']);
    }

    public function test_rejects_heading_out_of_range()
    {
        $response = $this->actingAs($this->driver)->postJson('/driver/location', [
            'lat' => 10.2443,
            'lng' => -66.8622,
            'heading' => 450,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['heading']);
    }

    // =============================================
    // CACHÉ Y DISPATCH
    // =============================================

    public function test_location_is_cached()
    {
        $this->actingAs($this->driver)->postJson('/driver/location', [
            'lat' => 10.2443,
            'lng' => -66.8622,
        ]);

        $cached = Cache::get("driver:{$this->driver->id}:current_location");
        $this->assertNotNull($cached);
        $this->assertEquals(10.2443, $cached['lat']);
        $this->assertEquals(-66.8622, $cached['lng']);
    }

    public function test_job_is_dispatched_on_location_update()
    {
        Queue::fake();

        $this->actingAs($this->driver)->postJson('/driver/location', [
            'lat' => 10.2443,
            'lng' => -66.8622,
            'speed' => 45.5,
            'heading' => 180,
        ]);

        Queue::assertPushed(ProcessLocationUpdateJob::class, function ($job) {
            return true;
        });
    }

    // =============================================
    // JOB EXECUTION (Sync mode - phpunit.xml)
    // =============================================

    public function test_job_creates_vehicle_location_record()
    {
        $job = new ProcessLocationUpdateJob(
            driverId: $this->driver->id,
            latitude: 10.2443,
            longitude: -66.8622,
            speed: 45.5,
            heading: 180.0,
        );

        $job->handle();

        $this->assertDatabaseHas('vehicle_locations', [
            'driver_id' => $this->driver->id,
            'latitude' => 10.2443000,
            'longitude' => -66.8622000,
            'speed' => 45.50,
            'heading' => 180.00,
        ]);
    }

    public function test_job_updates_user_snapshot_coordinates()
    {
        $job = new ProcessLocationUpdateJob(
            driverId: $this->driver->id,
            latitude: 10.5000,
            longitude: -66.9000,
        );

        $job->handle();

        $this->driver->refresh();
        $this->assertEquals(10.5000000, $this->driver->current_lat);
        $this->assertEquals(-66.9000000, $this->driver->current_lng);
    }

    public function test_job_links_to_trip_when_provided()
    {
        $trip = Trip::factory()->inProgress()->create([
            'passenger_id' => $this->passenger->id,
            'driver_id' => $this->driver->id,
        ]);

        $job = new ProcessLocationUpdateJob(
            driverId: $this->driver->id,
            latitude: 10.2443,
            longitude: -66.8622,
            tripId: $trip->id,
        );

        $job->handle();

        $this->assertDatabaseHas('vehicle_locations', [
            'driver_id' => $this->driver->id,
            'trip_id' => $trip->id,
        ]);
    }

    // =============================================
    // AUTORIZACIÓN
    // =============================================

    public function test_passenger_cannot_update_location()
    {
        $response = $this->actingAs($this->passenger)->postJson('/driver/location', [
            'lat' => 10.2443,
            'lng' => -66.8622,
        ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_update_location()
    {
        $response = $this->postJson('/driver/location', [
            'lat' => 10.2443,
            'lng' => -66.8622,
        ]);

        $response->assertStatus(401);
    }
}
