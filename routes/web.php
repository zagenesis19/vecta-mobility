<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TripController;      // <--- Asegúrate de tener esto
use App\Http\Controllers\AdminController;     // <--- Y esto
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Rutas Web
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

Route::get('/dashboard', function () {
    // Esta lógica la tenías en tu archivo original para mostrar los viajes
    $user = auth()->user();
    
    // Si es ADMIN, mostramos todos los viajes para el mapa de calor
    if ($user->email === 'admin@vecta.com') {
        $trips = \App\Models\Trip::all(); 
        return Inertia::render('Dashboard', ['trips' => $trips]);
    }

    // Si es Usuario normal (Pasajero o Chofer)
    $trips = \App\Models\Trip::query()
        ->where('passenger_id', $user->id)
        ->orWhere('driver_id', $user->id)
        ->with(['passenger', 'driver'])
        ->latest()
        ->take(5)
        ->get();

    return Inertia::render('Dashboard', ['trips' => $trips]);
})->middleware(['auth', 'verified'])->name('dashboard');

// --- GRUPO DE RUTAS DE PERFIL Y VIAJES ---
Route::middleware('auth')->group(function () {
    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Viajes (TripController)
    Route::get('/request-ride', [TripController::class, 'create'])->name('trip.create');
    Route::post('/request-ride', [TripController::class, 'store'])->name('trip.store');
    Route::post('/trip/{trip}/status', [TripController::class, 'updateStatus'])->name('trip.updateStatus');
}); // <--- AQUÍ CIERRA EL GRUPO ANTERIOR (Mira el punto y coma)

// --- RUTAS DE ADMINISTRADOR (NUEVAS) ---
Route::middleware(['auth'])->group(function () {
    // Ver la lista de pendientes
    Route::get('/admin/drivers', [AdminController::class, 'index'])->name('admin.drivers');
    
    // Aprobar a uno específico
    Route::put('/admin/drivers/{id}/approve', [AdminController::class, 'approve'])->name('admin.approve');
});

// --- FINAL DEL ARCHIVO ---
require __DIR__.'/auth.php';