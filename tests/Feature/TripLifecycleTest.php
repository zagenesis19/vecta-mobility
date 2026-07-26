<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Test del Ciclo de Vida Completo del Viaje.
 * Cubre: pending → accepted → in_progress → completed → review
 *        + cancelaciones + autorización
 */
class TripLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $passenger;
    private User $driver;
    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passenger = User::factory()->create([
            'role' => 'passenger',
            'phone_number' => '04241928802',
        ]);

        $this->driver = User::factory()->driver()->create([
            'phone_number' => '04142567890',
        ]);

        $this->vehicle = Vehicle::create([
            'user_id' => $this->driver->id,
            'type' => 'car',
            'model' => 'Toyota Corolla',
            'plate' => 'ABC123',
            'year' => 2020,
            'color' => 'Blanco',
        ]);
    }

    // =============================================
    // CREACIÓN DE VIAJE (PASAJERO)
    // =============================================

    public function test_passenger_can_create_trip()
    {
        $response = $this->actingAs($this->passenger)->post(route('trips.store'), [
            'origin' => 'Plaza Bolívar, Charallave',
            'destination' => 'Estación Ferrocarril Cúa',
            'origin_lat' => 10.2460,
            'origin_lng' => -66.8620,
            'destination_lat' => 10.1630,
            'destination_lng' => -66.8850,
            'payment_method' => 'Efectivo',
            'vehicle_type' => 'car',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('trips', [
            'passenger_id' => $this->passenger->id,
            'status' => 'pending',
            'origin_address' => 'Plaza Bolívar, Charallave',
            'passenger_snapshot_name' => $this->passenger->name,
        ]);
    }

    public function test_trip_creation_validates_required_fields()
    {
        $response = $this->actingAs($this->passenger)->post(route('trips.store'), [
            'origin' => '',
            'destination' => '',
        ]);

        $response->assertSessionHasErrors(['origin', 'destination', 'origin_lat', 'origin_lng', 'destination_lat', 'destination_lng', 'payment_method']);
    }

    // =============================================
    // ACEPTACIÓN DE VIAJE (CONDUCTOR)
    // =============================================

    public function test_driver_can_accept_pending_trip()
    {
        $trip = Trip::factory()->create([
            'passenger_id' => $this->passenger->id,
        ]);

        $response = $this->actingAs($this->driver)->put(route('trip.accept', $trip->id));

        $response->assertRedirect(route('dashboard'));
        $trip->refresh();

        $this->assertEquals('accepted', $trip->status);
        $this->assertEquals($this->driver->id, $trip->driver_id);
        $this->assertNotNull($trip->accepted_at);
        $this->assertEquals($this->driver->name, $trip->driver_snapshot_name);
    }

    public function test_driver_cannot_accept_non_pending_trip()
    {
        $trip = Trip::factory()->accepted()->create([
            'passenger_id' => $this->passenger->id,
        ]);

        $anotherDriver = User::factory()->driver()->create();

        $response = $this->actingAs($anotherDriver)->put(route('trip.accept', $trip->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // =============================================
    // INICIAR VIAJE (CONDUCTOR)
    // =============================================

    public function test_driver_can_start_accepted_trip()
    {
        $trip = Trip::factory()->create([
            'passenger_id' => $this->passenger->id,
            'driver_id' => $this->driver->id,
            'status' => 'accepted',
        ]);

        $response = $this->actingAs($this->driver)->put(route('trips.start', $trip->id));

        $response->assertRedirect(route('dashboard'));
        $trip->refresh();

        $this->assertEquals('in_progress', $trip->status);
        $this->assertNotNull($trip->started_at);
    }

    public function test_unauthorized_user_cannot_start_trip()
    {
        $trip = Trip::factory()->create([
            'passenger_id' => $this->passenger->id,
            'driver_id' => $this->driver->id,
            'status' => 'accepted',
        ]);

        $otherUser = User::factory()->create();
        $response = $this->actingAs($otherUser)->put(route('trips.start', $trip->id));

        $response->assertRedirect();
        $trip->refresh();
        $this->assertEquals('accepted', $trip->status);
    }

    // =============================================
    // FINALIZAR VIAJE (CONDUCTOR)
    // =============================================

    public function test_driver_can_finish_in_progress_trip()
    {
        $trip = Trip::factory()->inProgress()->create([
            'passenger_id' => $this->passenger->id,
            'driver_id' => $this->driver->id,
        ]);

        $response = $this->actingAs($this->driver)->put(route('trips.finish', $trip->id));

        $response->assertRedirect(route('dashboard'));
        $trip->refresh();

        $this->assertEquals('completed', $trip->status);
        $this->assertNotNull($trip->finished_at);
        $this->assertNotNull($trip->duration_minutes);
    }

    // =============================================
    // CANCELACIÓN
    // =============================================

    public function test_passenger_can_cancel_pending_trip()
    {
        $trip = Trip::factory()->create([
            'passenger_id' => $this->passenger->id,
        ]);

        $response = $this->actingAs($this->passenger)->delete(route('trip.cancel', $trip->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('trips', ['id' => $trip->id]);
    }

    public function test_driver_can_release_accepted_trip()
    {
        $trip = Trip::factory()->create([
            'passenger_id' => $this->passenger->id,
            'driver_id' => $this->driver->id,
            'status' => 'accepted',
        ]);

        $response = $this->actingAs($this->driver)->delete(route('trip.cancel', $trip->id));

        $response->assertRedirect();
        $trip->refresh();

        $this->assertEquals('pending', $trip->status);
        $this->assertNull($trip->driver_id);
    }

    public function test_cannot_cancel_in_progress_trip()
    {
        $trip = Trip::factory()->inProgress()->create([
            'passenger_id' => $this->passenger->id,
            'driver_id' => $this->driver->id,
        ]);

        $response = $this->actingAs($this->passenger)->delete(route('trip.cancel', $trip->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_cancel_with_reason_records_cancellation()
    {
        $trip = Trip::factory()->create([
            'passenger_id' => $this->passenger->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->passenger)->post(
            route('trip.cancelWithReason', $trip->id),
            ['reason' => 'Encontré otro transporte']
        );

        $response->assertRedirect(route('dashboard'));
        $trip->refresh();

        $this->assertEquals('cancelled', $trip->status);
        $this->assertEquals('Encontré otro transporte', $trip->cancellation_reason);
        $this->assertEquals('passenger', $trip->cancelled_by);
    }

    // =============================================
    // PAGO
    // =============================================

    public function test_driver_can_confirm_payment()
    {
        $trip = Trip::factory()->create([
            'passenger_id' => $this->passenger->id,
            'driver_id' => $this->driver->id,
            'status' => 'completed',
            'payment_confirmed' => false,
        ]);

        $response = $this->actingAs($this->driver)->post(route('trip.confirmPayment', $trip->id));

        $response->assertRedirect();
        $trip->refresh();

        $this->assertTrue($trip->payment_confirmed);
        $this->assertNotNull($trip->payment_confirmed_at);
    }

    public function test_passenger_cannot_confirm_payment()
    {
        $trip = Trip::factory()->create([
            'passenger_id' => $this->passenger->id,
            'driver_id' => $this->driver->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->passenger)->post(route('trip.confirmPayment', $trip->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // =============================================
    // CICLO COMPLETO E2E (Happy Path)
    // =============================================

    public function test_full_trip_lifecycle_happy_path()
    {
        // 1. Pasajero solicita viaje
        $this->actingAs($this->passenger)->post(route('trips.store'), [
            'origin' => 'Plaza Bolívar',
            'destination' => 'Estación Cúa',
            'origin_lat' => 10.2460,
            'origin_lng' => -66.8620,
            'destination_lat' => 10.1630,
            'destination_lng' => -66.8850,
            'payment_method' => 'Efectivo',
            'vehicle_type' => 'car',
        ]);

        $trip = Trip::where('passenger_id', $this->passenger->id)->first();
        $this->assertNotNull($trip);
        $this->assertEquals('pending', $trip->status);

        // 2. Conductor acepta
        $this->actingAs($this->driver)->put(route('trip.accept', $trip->id));
        $trip->refresh();
        $this->assertEquals('accepted', $trip->status);

        // 3. Conductor inicia
        $this->actingAs($this->driver)->put(route('trips.start', $trip->id));
        $trip->refresh();
        $this->assertEquals('in_progress', $trip->status);

        // 4. Conductor finaliza
        $this->actingAs($this->driver)->put(route('trips.finish', $trip->id));
        $trip->refresh();
        $this->assertEquals('completed', $trip->status);

        // 5. Conductor confirma pago
        $this->actingAs($this->driver)->post(route('trip.confirmPayment', $trip->id));
        $trip->refresh();
        $this->assertTrue($trip->payment_confirmed);

        // 6. Pasajero califica
        $this->actingAs($this->passenger)->post(route('trip.rate', $trip->id), [
            'rating' => 5,
            'comment' => '¡Excelente servicio!',
        ]);

        $this->assertDatabaseHas('reviews', [
            'trip_id' => $trip->id,
            'reviewer_id' => $this->passenger->id,
            'reviewed_id' => $this->driver->id,
            'rating' => 5,
        ]);

        // 7. Conductor califica al pasajero
        $this->actingAs($this->driver)->post(route('trip.rate', $trip->id), [
            'rating' => 4,
            'comment' => 'Buen pasajero',
        ]);

        $this->assertEquals(2, Review::where('trip_id', $trip->id)->count());
    }
}
