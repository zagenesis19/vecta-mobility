<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('passenger_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null');

            $table->string('origin_address');
            $table->string('destination_address');
            $table->decimal('origin_lat', 10, 7);
            $table->decimal('origin_lng', 10, 7);
            $table->decimal('destination_lat', 10, 7);
            $table->decimal('destination_lng', 10, 7);

            $table->enum('status', ['pending', 'accepted', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->decimal('price', 10, 2);
            $table->string('payment_method')->default('Efectivo');
            $table->decimal('distance', 8, 2)->nullable();
            $table->string('vehicle_type')->nullable();

            // Cancelación
            $table->text('cancellation_reason')->nullable();
            $table->enum('cancelled_by', ['passenger', 'driver'])->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Confirmación de pago
            $table->boolean('payment_confirmed')->default(false);
            $table->timestamp('payment_confirmed_at')->nullable();

            // Métricas y tiempos
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('driver_arrived_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->integer('duration_minutes')->nullable();

            // Snapshots (Denormalización)
            $table->string('passenger_snapshot_name')->nullable();
            $table->string('passenger_snapshot_phone')->nullable();
            $table->string('driver_snapshot_name')->nullable();
            $table->string('driver_snapshot_phone')->nullable();
            $table->string('driver_snapshot_photo')->nullable();
            $table->json('vehicle_snapshot_data')->nullable();

            // Calificaciones en el viaje
            $table->integer('driver_rating')->nullable();
            $table->text('driver_comment')->nullable();
            $table->integer('passenger_rating')->nullable();
            $table->text('passenger_comment')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
