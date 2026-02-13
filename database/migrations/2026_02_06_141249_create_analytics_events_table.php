<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();

            // User tracking
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_id')->index(); // For guests / sessions

            // Event Details
            $table->string('event_type'); // 'page_view', 'click', 'error', 'identity_verification_start'
            $table->string('target')->nullable(); // URL or Button ID
            $table->json('meta')->nullable(); // Extra data: { browser: 'Chrome', ... }

            $table->timestamps();

            // Index for fast reporting
            $table->index(['event_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
