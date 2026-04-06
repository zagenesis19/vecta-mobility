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
        // 1. Agregar campos de estado y sanción a la tabla users
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('is_approved'); // Por defecto activo
            $table->text('ban_reason')->nullable()->after('is_active'); // Razón de la sanción
        });

        // 2. Crear tabla de mensajes administrativos
        Schema::create('admin_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Destinatario
            $table->foreignId('admin_id')->nullable()->constrained('users')->onDelete('set null'); // Remitente (Admin)
            $table->string('subject');
            $table->text('body');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_messages');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'ban_reason']);
        });
    }
};
