<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->tinyInteger('driver_rating')->nullable()->after('status'); // Calificación al conductor (dada por pasajero)
            $table->text('driver_comment')->nullable()->after('driver_rating');
            
            $table->tinyInteger('passenger_rating')->nullable()->after('driver_comment'); // Calificación al pasajero (dada por conductor)
            $table->text('passenger_comment')->nullable()->after('passenger_rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn(['driver_rating', 'driver_comment', 'passenger_rating', 'passenger_comment']);
        });
    }
};
