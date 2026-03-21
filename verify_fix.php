<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$userIds = [1, 14, 15];

foreach ($userIds as $id) {
    $user = App\Models\User::find($id);
    if (!$user) {
        echo "User {$id} not found.\n";
        continue;
    }
    
    echo "Checking for User: {$user->name} (ID: {$user->id})\n";
    
    $pendingActionTrip = App\Models\Trip::where('passenger_id', $user->id)
        ->where('status', 'completed')
        ->whereNotNull('driver_id') // 🔥 MI FIX
        ->whereDoesntHave('reviews', function($sq) use ($user) {
            $sq->where('reviewer_id', $user->id);
        })
        ->latest()
        ->first();
        
    if ($pendingActionTrip) {
        echo "  [FOUND] pendingActionTrip: {$pendingActionTrip->id}\n";
    } else {
        echo "  [OK] No pendingActionTrip found.\n";
    }
}
