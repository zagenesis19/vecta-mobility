<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\AdminController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Rutas Web - Vecta Mobility (Versión Completa)
|--------------------------------------------------------------------------
*/

// 1. PÁGINA DE BIENVENIDA
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// 2. DASHBOARD INTELIGENTE (Detecta Roles)
Route::get('/dashboard', function () {
    $user = auth()->user();

    // A. ADMINISTRADOR
    if ($user->role === 'admin' || $user->email === 'admin@vecta.com') {
        $trips = \App\Models\Trip::with(['passenger', 'driver'])->latest()->get(); 
        return Inertia::render('Dashboard', [
            'trips' => $trips,
            'userRole' => 'admin'
        ]);
    }

    // B. CONDUCTOR
    if ($user->role === 'driver') {
        // Viajes disponibles para aceptar (Pendientes y sin chofer)
        $availableTrips = \App\Models\Trip::where('status', 'pending')
            ->whereNull('driver_id')
            ->with('passenger')
            ->latest()
            ->get();

        // Mis viajes activos/pasados
        $myTrips = \App\Models\Trip::where('driver_id', $user->id)
            ->with('passenger')
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Dashboard', [
            'trips' => $myTrips,
            'availableTrips' => $availableTrips,
            'userRole' => 'driver'
        ]);
    }

    // C. PASAJERO (Default)
    $trips = \App\Models\Trip::where('passenger_id', $user->id)
        ->with('driver')
        ->latest()
        ->take(5)
        ->get();

    return Inertia::render('Dashboard', [
        'trips' => $trips,
        'userRole' => 'passenger'
    ]);

})->middleware(['auth', 'verified'])->name('dashboard');

// 3. GRUPO DE RUTAS AUTENTICADAS
Route::middleware('auth')->group(function () {
    // --- PERFIL ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // --- GESTIÓN DE VIAJES (PASAJERO) ---
    Route::get('/request-ride', [TripController::class, 'create'])->name('trip.create');
    Route::post('/request-ride', [TripController::class, 'store'])->name('trip.store');
    
    // --- GESTIÓN DE VIAJES (CONDUCTOR - FLUJO COMPLETO) ---
    // Aceptar viaje
    Route::put('/trip/{id}/accept', [TripController::class, 'accept'])->name('trip.accept');
    // Iniciar viaje (Recogió al pasajero)
    Route::put('/trip/{id}/start', [TripController::class, 'startTrip'])->name('trip.start');
    // Finalizar viaje (Llegó al destino y cobró)
    Route::put('/trip/{id}/finish', [TripController::class, 'finishTrip'])->name('trip.finish');
    
    // Actualizar estado genérico (si se usa)
    Route::post('/trip/{trip}/status', [TripController::class, 'updateStatus'])->name('trip.updateStatus');
});

// 4. RUTAS DE ADMINISTRADOR
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/drivers', [AdminController::class, 'index'])->name('admin.drivers');
    Route::put('/admin/drivers/{id}/approve', [AdminController::class, 'approve'])->name('admin.approve');
});

require __DIR__.'/auth.php';