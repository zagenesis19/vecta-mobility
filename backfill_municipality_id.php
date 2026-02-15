<?php
// Backfill municipality_id from legacy municipality string field
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$municipalities = App\Models\Municipality::all();
$drivers = App\Models\User::where('role', 'driver')
    ->whereNull('municipality_id')
    ->whereNotNull('municipality')
    ->get();

$updated = 0;
foreach ($drivers as $driver) {
    $legacyValue = $driver->municipality;
    foreach ($municipalities as $muni) {
        if (
            str_contains($legacyValue, $muni->name) || 
            ($muni->capital && str_contains($legacyValue, $muni->capital))
        ) {
            $driver->municipality_id = $muni->id;
            $driver->save();
            echo "Updated: {$driver->name} -> {$muni->name} (ID: {$muni->id})\n";
            $updated++;
            break;
        }
    }
}

echo "\nBackfilled {$updated} drivers.\n";

// Now show the updated stats
echo "\n=== UPDATED DRIVER DATA ===\n";
$allDrivers = App\Models\User::where('role', 'driver')->where('is_approved', true)->get();
foreach ($allDrivers as $d) {
    echo "ID: {$d->id} | {$d->name} | municipality_id: " . ($d->municipality_id ?? 'NULL') . " | municipality: " . ($d->municipality ?? 'NULL') . "\n";
}
