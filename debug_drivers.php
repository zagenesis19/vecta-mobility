<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== MUNICIPALITIES ===\n";
$munis = App\Models\Municipality::all();
foreach ($munis as $m) {
    echo "ID: {$m->id} | Name: {$m->name} | Capital: {$m->capital}\n";
}

echo "\n=== DRIVERS ===\n";
$drivers = App\Models\User::where('role', 'driver')->get();
foreach ($drivers as $d) {
    echo "ID: {$d->id} | Name: {$d->name} | municipality_id: " . ($d->municipality_id ?? 'NULL') . " | municipality(string): " . ($d->municipality ?? 'NULL') . " | approved: {$d->is_approved}\n";
}

echo "\n=== STATS THAT WOULD BE GENERATED ===\n";
$driverStats = [];
foreach ($munis as $municipality) {
    if ($municipality->name) {
        $driverStats[$municipality->name] = 0;
    }
}

$counts = App\Models\User::where('role', 'driver')
    ->where('is_approved', true)
    ->whereNotNull('municipality_id')
    ->select('municipality_id', \DB::raw('count(*) as count'))
    ->groupBy('municipality_id')
    ->get();

foreach ($counts as $count) {
    $muni = $munis->find($count->municipality_id);
    if ($muni && $muni->name) {
        $driverStats[$muni->name] += $count->count;
    }
}

echo json_encode($driverStats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
