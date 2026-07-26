<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Trip;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test del Sistema de Calificaciones (Reviews).
 * Cubre: permisos, duplicados, validación, y cálculo de promedio.
 */
class ReviewSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $passenger;
    private User $driver;
    private Trip $completedTrip;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passenger = User::factory()->create(['role' => 'passenger']);
        $this->driver = User::factory()->driver()->create();

        $this->completedTrip = Trip::factory()->create([
            'passenger_id' => $this->passenger->id,
            'driver_id' => $this->driver->id,
            'status' => 'completed',
            'finished_at' => now(),
        ]);
    }

    // =============================================
    // CREACIÓN DE REVIEWS
    // =============================================

    public function test_passenger_can_rate_driver()
    {
        $response = $this->actingAs($this->passenger)->post(
            route('trip.rate', $this->completedTrip->id),
            ['rating' => 5, 'comment' => 'Excelente conductor']
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('reviews', [
            'trip_id' => $this->completedTrip->id,
            'reviewer_id' => $this->passenger->id,
            'reviewed_id' => $this->driver->id,
            'rating' => 5,
        ]);
    }

    public function test_driver_can_rate_passenger()
    {
        $response = $this->actingAs($this->driver)->post(
            route('trip.rate', $this->completedTrip->id),
            ['rating' => 4, 'comment' => 'Buen pasajero']
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('reviews', [
            'trip_id' => $this->completedTrip->id,
            'reviewer_id' => $this->driver->id,
            'reviewed_id' => $this->passenger->id,
            'rating' => 4,
        ]);
    }

    // =============================================
    // VALIDACIÓN
    // =============================================

    public function test_rating_must_be_between_1_and_5()
    {
        $response = $this->actingAs($this->passenger)->post(
            route('trip.rate', $this->completedTrip->id),
            ['rating' => 6]
        );

        $response->assertSessionHasErrors(['rating']);
    }

    public function test_rating_is_required()
    {
        $response = $this->actingAs($this->passenger)->post(
            route('trip.rate', $this->completedTrip->id),
            ['comment' => 'Sin estrellas']
        );

        $response->assertSessionHasErrors(['rating']);
    }

    // =============================================
    // DUPLICADOS (updateOrCreate)
    // =============================================

    public function test_duplicate_review_updates_instead_of_failing()
    {
        // Primera calificación
        $this->actingAs($this->passenger)->post(
            route('trip.rate', $this->completedTrip->id),
            ['rating' => 3, 'comment' => 'Regular']
        );

        // Segunda calificación (debería actualizar)
        $this->actingAs($this->passenger)->post(
            route('trip.rate', $this->completedTrip->id),
            ['rating' => 5, 'comment' => 'Cambié de opinión, excelente']
        );

        // Solo debe haber 1 review del pasajero
        $count = Review::where('trip_id', $this->completedTrip->id)
            ->where('reviewer_id', $this->passenger->id)
            ->count();

        $this->assertEquals(1, $count);

        // Y debe tener el rating actualizado
        $review = Review::where('trip_id', $this->completedTrip->id)
            ->where('reviewer_id', $this->passenger->id)
            ->first();

        $this->assertEquals(5, $review->rating);
        $this->assertEquals('Cambié de opinión, excelente', $review->comment);
    }

    // =============================================
    // AUTORIZACIÓN
    // =============================================

    public function test_stranger_cannot_rate_trip()
    {
        $stranger = User::factory()->create();

        $response = $this->actingAs($stranger)->post(
            route('trip.rate', $this->completedTrip->id),
            ['rating' => 5]
        );

        $response->assertRedirect();
        $this->assertEquals(0, Review::where('trip_id', $this->completedTrip->id)->count());
    }

    public function test_cannot_rate_trip_without_driver()
    {
        $tripNoDriver = Trip::factory()->create([
            'passenger_id' => $this->passenger->id,
            'driver_id' => null,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->passenger)->post(
            route('trip.rate', $tripNoDriver->id),
            ['rating' => 5]
        );

        $response->assertRedirect();
        $this->assertEquals(0, Review::where('trip_id', $tripNoDriver->id)->count());
    }

    // =============================================
    // CÁLCULO DE PROMEDIO (User Model)
    // =============================================

    public function test_average_rating_is_calculated_correctly()
    {
        // Creamos 3 reviews con ratings 5, 4, 3 = promedio 4.0
        $trips = Trip::factory()->count(3)->create([
            'passenger_id' => $this->passenger->id,
            'driver_id' => $this->driver->id,
            'status' => 'completed',
        ]);

        Review::create(['trip_id' => $trips[0]->id, 'reviewer_id' => $this->passenger->id, 'reviewed_id' => $this->driver->id, 'rating' => 5]);
        Review::create(['trip_id' => $trips[1]->id, 'reviewer_id' => $this->passenger->id, 'reviewed_id' => $this->driver->id, 'rating' => 4]);
        Review::create(['trip_id' => $trips[2]->id, 'reviewer_id' => $this->passenger->id, 'reviewed_id' => $this->driver->id, 'rating' => 3]);

        $this->driver->refresh();

        $this->assertEquals(4.0, $this->driver->average_rating);
        $this->assertEquals(3, $this->driver->total_ratings);
    }

    public function test_user_with_no_reviews_has_zero_rating()
    {
        $newUser = User::factory()->create();

        $this->assertEquals(0, $newUser->average_rating);
        $this->assertEquals(0, $newUser->total_ratings);
    }
}
