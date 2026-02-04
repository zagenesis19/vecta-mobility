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
            $table->string('gender')->nullable(); // male, female, other
            $table->boolean('terms_accepted')->default(false);
            
            // Ubicación
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('municipality')->nullable();
            
            // phone_verified_at YA EXISTE en create_users_table, no lo agregamos aquí
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'gender', 
                'terms_accepted', 
                'country', 
                'state', 
                'municipality'
            ]);
        });
    }
};
