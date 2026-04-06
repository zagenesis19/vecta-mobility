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
        Schema::table('users', function (Blueprint $table) {
            // Columnas para el GPS en vivo (Latitud y Longitud)
            // Usamos decimal(10,7) para alta precisión en mapas
            $table->decimal('current_lat', 10, 7)->nullable()->after('email');
            $table->decimal('current_lng', 10, 7)->nullable()->after('current_lat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Borrar columnas si revertimos la migración
            $table->dropColumn(['current_lat', 'current_lng']);
        });
    }
};