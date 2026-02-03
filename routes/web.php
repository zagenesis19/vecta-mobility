<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ReviewController; // <--- AGREGADO: Importamos el controlador de reseñas
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Trip; 

/*
|--------------------------------------------------------------------------
| Rutas Web - Vecta Mobility (Versión Final con Calificaciones ⭐)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// 2. DASHBOARD INTELIGENTE
Route::get('/dashboard', function () {
    $user = auth()->user();

    // A. ADMINISTRADOR
    if ($user->role === 'admin' || $user->email === 'admin@vecta.com') {
        $trips = Trip::with(['passenger', 'driver'])->latest()->get(); 
        
        // 🔥 Obtener lat/lng de conductores para el Mapa Térmico
        $driverLocations = User::where('role', 'driver')
            ->whereNotNull('current_lat')
            ->whereNotNull('current_lng')
            ->get(['current_lat', 'current_lng'])
            ->map(function($driver) {
                return [$driver->current_lat, $driver->current_lng];
            });
        
        return Inertia::render('Dashboard', [
            'trips' => $trips,
            'userRole' => 'admin',
            'driverLocations' => $driverLocations 
        ]);
    }

    // B. CONDUCTOR
    if ($user->role === 'driver') {
        $driverType = $user->vehicle ? $user->vehicle->type : 'car'; 

        $availableTrips = Trip::where('status', 'pending')
            ->whereNull('driver_id')
            ->where('passenger_id', '!=', $user->id)
            ->where('vehicle_type', $driverType)
            ->with('passenger')
            ->latest()
            ->get();

        $myTrips = Trip::where('driver_id', $user->id)
            ->with('passenger')
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Dashboard', [
            'trips' => $myTrips,
            'myTrips' => $myTrips,
            'availableTrips' => $availableTrips,
            'userRole' => 'driver',
            'isApproved' => (bool) $user->is_approved, 
        ]);
    }
    
    // C. PASAJERO
    $trips = Trip::where('passenger_id', $user->id)
        ->with('driver')
        ->latest()
        ->take(5)
        ->get();

    $currentTrip = Trip::where('passenger_id', $user->id)
        ->whereIn('status', ['pending', 'accepted', 'in_progress', 'completed'])
        ->with('driver')
        ->latest()
        ->first();

    return Inertia::render('Dashboard', [
        'trips' => $trips,       
        'currentTrip' => $currentTrip, 
        'userRole' => 'passenger'
    ]);

})->middleware(['auth'])->name('dashboard');

// 3. GRUPO DE RUTAS AUTENTICADAS
Route::middleware('auth')->group(function () {
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/identity', [ProfileController::class, 'updateIdentity'])->name('profile.identity.update');
    
    Route::get('/request-ride', [TripController::class, 'create'])->name('trips.create');
    Route::post('/request-ride', [TripController::class, 'store'])->name('trips.store');
    Route::delete('/trip/{trip}/cancel', [TripController::class, 'cancel'])->name('trip.cancel');
    
    Route::put('/trip/{id}/accept', [TripController::class, 'accept'])->name('trip.accept');
    Route::put('/trip/{id}/start', [TripController::class, 'startTrip'])->name('trips.start');
    Route::put('/trip/{id}/finish', [TripController::class, 'finishTrip'])->name('trips.finish');
    Route::post('/driver/location', [TripController::class, 'updateLocation'])->name('driver.location');

    // ⭐ SISTEMA DE CALIFICACIONES (NUEVO)
    Route::post('/trip/{trip}/rate', [ReviewController::class, 'store'])->name('trip.rate');
});

// 4. RUTAS DE ADMINISTRADOR
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/verifications', [AdminController::class, 'verifications'])->name('admin.verifications');
    Route::post('/verifications/{user}/approve', [AdminController::class, 'approveIdentity'])->name('admin.verifications.approve');
    Route::post('/verifications/{user}/reject', [AdminController::class, 'rejectIdentity'])->name('admin.verifications.reject');
    
    Route::get('/drivers', [AdminController::class, 'index'])->name('admin.drivers');
    Route::put('/drivers/{id}/approve', [AdminController::class, 'approve'])->name('admin.approve');
    Route::delete('/drivers/{id}/reject', [AdminController::class, 'reject'])->name('admin.reject');
});

/*
|--------------------------------------------------------------------------
| 🧪 RUTAS DE SIMULACIÓN MASIVA (300 Conductores) 🔥
|--------------------------------------------------------------------------
*/

Route::get('/simular-conductores', function () {
    // Aumentamos el tiempo de ejecución por si tu PC es lenta creando 300 usuarios
    set_time_limit(300); 

    $latBase = 10.2443; // Charallave Centro
    $lngBase = -66.8622;

    // 🔥 Creamos 300 conductores dispersos por toda la ciudad
    for ($i = 1; $i <= 300; $i++) {
        if (!User::where('email', 'fantasma'.$i.'@vecta.com')->exists()) {
            User::create([
                'name' => 'Chofer Masivo ' . $i,
                'email' => 'fantasma' . $i . '@vecta.com',
                'password' => bcrypt('password'),
                'role' => 'driver',
                'is_approved' => true,
                // Aumentamos el rango de dispersión a 200 para cubrir más mapa
                'current_lat' => $latBase + (rand(-200, 200) / 10000), 
                'current_lng' => $lngBase + (rand(-200, 200) / 10000),
            ]);
        }
    }
    return "✅ ¡Éxito! 300 conductores creados. Ve al Dashboard y verás fuego 🔥";
});

Route::get('/limpiar-simulacion', function () {
    User::where('email', 'like', 'fantasma%@vecta.com')->delete();
    return "🗑️ Limpieza completada. Ejército fantasma eliminado.";
});

require __DIR__.'/auth.php';