<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            
            // ✅ CORRECTO: Usamos user_id para conectar con la tabla users
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('type')->default('car'); 
            $table->string('model')->nullable();
            $table->string('plate')->nullable();
            $table->integer('year')->nullable();
            $table->string('color')->nullable();
            $table->string('photo_path')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};