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
            // Nota: license_file y profile_photo_path ya existen
            
            // Foto de la Cédula (Archivo)
            if (!Schema::hasColumn('users', 'id_card_photo_path')) {
                $table->string('id_card_photo_path')->nullable()->after('id_card_number');
            }
            
            // Certificado Médico
            $table->string('medical_certificate_file')->nullable()->after('license_file');
            
            // RIF (Registro de Información Fiscal)
            $table->string('rif_file')->nullable()->after('medical_certificate_file');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['id_card_photo_path', 'medical_certificate_file', 'rif_file']);
        });
    }
};
