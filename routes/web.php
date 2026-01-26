<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\AdminController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\User; // Importamos el modelo User para las consultas

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

})->middleware(['auth', 'verified'])->name('dashboard');

// 3. GRUPO DE RUTAS AUTENTICADAS
Route::middleware('auth')->group(function () {
    // --- PERFIL ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // --- GESTIÓN DE VIAJES (PASAJERO) ---
    Route::get('/request-ride', [TripController::class, 'create'])->name('trips.create');
    Route::post('/request-ride', [TripController::class, 'store'])->name('trips.store');
    
    // 🔥 RUTA PARA CANCELAR VIAJE
    Route::delete('/trip/{trip}/cancel', [TripController::class, 'cancel'])->name('trip.cancel');

    Route::post('/trip/{trip}/status', [TripController::class, 'updateStatus'])->name('trip.updateStatus');

    // --- GESTIÓN DE VIAJES (CONDUCTOR) ---
    // Aceptar
    Route::put('/trip/{id}/accept', [TripController::class, 'accept'])->name('trip.accept');
    
    // 🛑 AQUÍ ESTABA EL ERROR: Cambiamos 'trip.start' por 'trips.start' (con S)
    Route::put('/trip/{id}/start', [TripController::class, 'startTrip'])->name('trips.start');
    
    // 🛑 AQUÍ ESTABA EL ERROR: Cambiamos 'trip.finish' por 'trips.finish' (con S)
    Route::put('/trip/{id}/finish', [TripController::class, 'finishTrip'])->name('trips.finish');
});

// 4. RUTAS DE ADMINISTRADOR (Gestión de Choferes)
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/drivers', [AdminController::class, 'index'])->name('admin.drivers');
    
    // Aprobar Conductor
    Route::put('/admin/drivers/{id}/approve', [AdminController::class, 'approve'])->name('admin.approve');
    
    // Rechazar Conductor
    Route::delete('/admin/drivers/{id}/reject', [AdminController::class, 'reject'])->name('admin.reject');
});

require __DIR__.'/auth.php';