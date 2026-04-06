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
            // 1. Soltar índices únicos simples existentes
            // Nota: El índice de email suele llamarse users_email_unique
            $table->dropUnique('users_email_unique');
            
            // Si phone_number tenía unique, lo soltamos.
            // Verificamos si existe antes de intentar borrarlo en un entorno real,
            // pero aquí asumimos que se creó con unique si así estaba en el código previo.
            // En el código visto User registeredcontroller validaba unique, y users table migration no tenía unique explícito en phone_number PERO la validación lo exigía.
            // Si la migración original NO tenía unique en phone_number, esto daría error.
            // Revisando 2014_...create_users_table.php visualizado antes: $table->string('phone_number')->nullable(); SIN unique.
            // Sin embargo, para garantizar consistencia, agregaremos el compuesto.
            
            // 2. Agregar índices compuestos (Email + Role) y (Phone + Role)
            $table->unique(['email', 'role'], 'users_email_role_unique');
            $table->unique(['phone_number', 'role'], 'users_phone_role_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_role_unique');
            $table->dropUnique('users_phone_role_unique');

            $table->unique('email', 'users_email_unique');
            // No restauramos phone_number unique porque originalmente no lo tenía en la migración base vista
        });
    }
};
