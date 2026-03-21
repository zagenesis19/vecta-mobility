<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- USERS ---\n";
foreach (App\Models\User::all(['id', 'name', 'role']) as $u) {
    echo "ID: {$u->id} | Name: {$u->name} | Role: {$u->role}\n";
}

echo "\n--- COMPLETED TRIPS ---\n";
$trips = App\Models\Trip::where('status', 'completed')->get();
if ($trips->isEmpty()) {
    echo "No completed trips in DB.\n";
} else {
    foreach ($trips as $t) {
        $reviews = App\Models\Review::where('trip_id', $t->id)->count();
        echo "Trip ID: {$t->id} | Passenger: {$t->passenger_id} | Driver: {$t->driver_id} | Reviews: {$reviews}\n";
    }
}
