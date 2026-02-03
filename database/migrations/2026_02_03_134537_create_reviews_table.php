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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            
            // Relación con el viaje
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            
            // ¿Quién califica? (Ej: Pasajero)
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            
            // ¿A quién califican? (Ej: Conductor)
            $table->foreignId('reviewed_id')->constrained('users')->onDelete('cascade');
            
            // La puntuación (1 a 5 estrellas)
            $table->integer('rating');
            
            // Comentario opcional
            $table->text('comment')->nullable();
            
            $table->timestamps();

            // Evitar que la misma persona califique 2 veces el mismo viaje
            $table->unique(['trip_id', 'reviewer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};