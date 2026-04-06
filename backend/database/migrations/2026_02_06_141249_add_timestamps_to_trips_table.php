<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            // Needed for "Wait Time" (Accepted -> Arrived)
            if (!Schema::hasColumn('trips', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('trips', 'driver_arrived_at')) {
                $table->timestamp('driver_arrived_at')->nullable()->after('accepted_at');
            }
            
            // Needed for duration check
            if (!Schema::hasColumn('trips', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('driver_arrived_at');
            }
            if (!Schema::hasColumn('trips', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('started_at'); 
            }
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn(['accepted_at', 'driver_arrived_at', 'started_at', 'completed_at']);
        });
    }
};
