<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\AdminController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Rutas Web - Vecta Mobility
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

// 2. DASHBOARD INTELIGENTE (Detecta Roles y envía datos)
Route::get('/dashboard', function () {
    $user = auth()->user();

    // =========================================================
    // A. ADMINISTRADOR
    // =========================================================
    if ($user->role === 'admin' || $user->email === 'admin@vecta.com') {
        
        // 1. Obtener viajes recientes
        $trips = \App\Models\Trip::with(['passenger', 'driver'])->latest()->get(); 
        
        // 2. Buscar conductores pendientes de aprobación
        $pendingDrivers = User::where('role', 'driver')
            ->where('is_approved', false) 
            ->get();

        return Inertia::render('Dashboard', [
            'trips' => $trips,
            'pendingDrivers' => $pendingDrivers,
            'userRole' => 'admin'
        ]);
    }

    // =========================================================
    // B. CONDUCTOR
    // =========================================================
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
            'myTrips' => $myTrips,
            'availableTrips' => $availableTrips,
            'userRole' => 'driver',
            
            // Enviamos el estado del candado
            'isApproved' => (bool) $user->is_approved, 
        ]);
    }

    // =========================================================
    // C. PASAJERO (Default)
    // =========================================================
    $trips = \App\Models\Trip::where('passenger_id', $user->id)
        ->with('driver')
        ->latest()
        ->take(5)
        ->get();

    return Inertia::render('Dashboard', [
        'trips' => $trips,
        'userRole' => 'passenger'
    ]);

})->middleware(['auth'])->name('dashboard'); // <--- AQUÍ HICE EL CAMBIO (Quité 'verified')

// 3. GRUPO DE RUTAS AUTENTICADAS
Route::middleware('auth')->group(function () {
    
    // --- PERFIL ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 🔥 RUTAS DE IDENTIDAD
    Route::post('/profile/identity', [ProfileController::class, 'updateIdentity'])->name('profile.identity.update');
    
    // --- GESTIÓN DE VIAJES (PASAJERO) ---
    Route::get('/request-ride', [TripController::class, 'create'])->name('trips.create');
    Route::post('/request-ride', [TripController::class, 'store'])->name('trips.store');
    Route::delete('/trip/{trip}/cancel', [TripController::class, 'cancel'])->name('trip.cancel');
    Route::post('/trip/{trip}/status', [TripController::class, 'updateStatus'])->name('trip.updateStatus');

    // --- GESTIÓN DE VIAJES (CONDUCTOR) ---
    Route::put('/trip/{id}/accept', [TripController::class, 'accept'])->name('trip.accept');
    Route::put('/trip/{id}/start', [TripController::class, 'startTrip'])->name('trip.start');
    Route::put('/trip/{id}/finish', [TripController::class, 'finishTrip'])->name('trip.finish');
});

// 4. RUTAS DE ADMINISTRADOR
Route::middleware(['auth'])->prefix('admin')->group(function () {
    // Verificaciones de Identidad
    Route::get('/verifications', [AdminController::class, 'verifications'])->name('admin.verifications');
    Route::post('/verifications/{user}/approve', [AdminController::class, 'approveIdentity'])->name('admin.verifications.approve');
    Route::post('/verifications/{user}/reject', [AdminController::class, 'rejectIdentity'])->name('admin.verifications.reject');
    
    // Gestión de Conductores
    Route::get('/drivers', [AdminController::class, 'index'])->name('admin.drivers');
    Route::put('/drivers/{id}/approve', [AdminController::class, 'approve'])->name('admin.approve');
    Route::delete('/drivers/{id}/reject', [AdminController::class, 'reject'])->name('admin.reject');
});

require __DIR__.'/auth.php';