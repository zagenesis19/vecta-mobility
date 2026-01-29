<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\AdminController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Trip; // Importamos el Modelo Trip

/*
|--------------------------------------------------------------------------
| Rutas Web - Vecta Mobility (Versión Fusionada Definitiva)
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

// 2. DASHBOARD INTELIGENTE (CEREBRO MEJORADO 🧠)
Route::get('/dashboard', function () {
    $user = auth()->user();

    // A. ADMINISTRADOR (Tu lógica intacta)
    if ($user->role === 'admin' || $user->email === 'admin@vecta.com') {
        $trips = Trip::with(['passenger', 'driver'])->latest()->get(); 
        $pendingDrivers = User::where('role', 'driver')->where('is_approved', false)->get();

        return Inertia::render('Dashboard', [
            'trips' => $trips,
            'pendingDrivers' => $pendingDrivers,
            'userRole' => 'admin'
        ]);
    }

    // B. CONDUCTOR
    if ($user->role === 'driver') {
        
        // 1. Averiguar qué vehículo tiene este chofer
        // CORRECCIÓN: Quitamos ->load('vehicle') y accedemos directo.
        // Laravel buscará el vehículo automáticamente si existe.
        $driverType = $user->vehicle ? $user->vehicle->type : 'car'; 

        // 2. Filtrar viajes disponibles
        $availableTrips = Trip::where('status', 'pending')
            ->whereNull('driver_id')
            ->where('passenger_id', '!=', $user->id)
            ->where('vehicle_type', $driverType) // <--- Filtro por tipo de vehículo
            ->with('passenger')
            ->latest()
            ->get();

        // 3. Mis viajes realizados (Historial)
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
    // C. PASAJERO (Default)
    
    // 1. Historial (Lo que ya tenías)
    $trips = Trip::where('passenger_id', $user->id)
        ->with('driver')
        ->latest()
        ->take(5)
        ->get();

    // 🔥 2. VIAJE ACTIVO (NUEVO: Vital para que el mapa sepa qué pintar)
    // Buscamos si hay un viaje pendiente, aceptado o completado (para pagar)
    $currentTrip = Trip::where('passenger_id', $user->id)
        ->whereIn('status', ['pending', 'accepted', 'in_progress', 'completed'])
        ->latest()
        ->first();

    return Inertia::render('Dashboard', [
        'trips' => $trips,       // Historial
        'currentTrip' => $currentTrip, // 🔥 La tarjeta de estado actual
        'userRole' => 'passenger'
    ]);

})->middleware(['auth'])->name('dashboard');

// 3. GRUPO DE RUTAS AUTENTICADAS
Route::middleware('auth')->group(function () {
    
    // --- PERFIL ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 🔥 IDENTIDAD (Tus rutas - Fase 5)
    Route::post('/profile/identity', [ProfileController::class, 'updateIdentity'])->name('profile.identity.update');
    
    // --- GESTIÓN DE VIAJES ---
    
    // Pasajero
    Route::get('/request-ride', [TripController::class, 'create'])->name('trips.create');
    Route::post('/request-ride', [TripController::class, 'store'])->name('trips.store');
    Route::delete('/trip/{trip}/cancel', [TripController::class, 'cancel'])->name('trip.cancel');
    
    // 🔥 Corrección de nombres para coincidir con Vue (trips.start vs trip.start)
    // Conductor
    Route::put('/trip/{id}/accept', [TripController::class, 'accept'])->name('trip.accept');
    Route::put('/trip/{id}/start', [TripController::class, 'startTrip'])->name('trips.start'); // OJO: nombre plural
    Route::put('/trip/{id}/finish', [TripController::class, 'finishTrip'])->name('trips.finish'); // OJO: nombre plural
});

// 4. RUTAS DE ADMINISTRADOR
Route::middleware(['auth'])->prefix('admin')->group(function () {
    
    // --- VERIFICACIONES DE IDENTIDAD (Tus rutas) ---
    Route::get('/verifications', [AdminController::class, 'verifications'])->name('admin.verifications');
    Route::post('/verifications/{user}/approve', [AdminController::class, 'approveIdentity'])->name('admin.verifications.approve');
    Route::post('/verifications/{user}/reject', [AdminController::class, 'rejectIdentity'])->name('admin.verifications.reject');
    
    // --- GESTIÓN DE CHOFERES (Rutas de Argenis) ---
    Route::get('/drivers', [AdminController::class, 'index'])->name('admin.drivers');
    Route::put('/drivers/{id}/approve', [AdminController::class, 'approve'])->name('admin.approve');
    Route::delete('/drivers/{id}/reject', [AdminController::class, 'reject'])->name('admin.reject');
});

require __DIR__.'/auth.php';