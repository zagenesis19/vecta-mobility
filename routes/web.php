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
    $user = auth()->user();

    // --- CASO 1: ADMINISTRADOR ---
    // Si tu usuario admin tiene el rol 'admin' o el correo específico
    if ($user->role === 'admin' || $user->email === 'admin@vecta.com') {
        // El jefe ve todo para los mapas y estadísticas
        $trips = \App\Models\Trip::with(['passenger', 'driver'])->latest()->get(); 
        return Inertia::render('Dashboard', [
            'trips' => $trips,
            'userRole' => 'admin' // Le avisamos al frontend quién es
        ]);
    }

    // --- CASO 2: CONDUCTOR ---
    if ($user->role === 'driver') {
        // A. Viajes Disponibles (Status 'pending' y SIN chofer asignado)
        // Esto es lo que saldrá en la "Tarjeta de Alerta" para que acepte
        $availableTrips = \App\Models\Trip::where('status', 'pending')
            ->whereNull('driver_id')
            ->with('passenger') // Traemos datos del pasajero para saber a quién recoger
            ->latest()
            ->get();

        // B. Mis Viajes (Los que este chofer ya aceptó o completó)
        $myTrips = \App\Models\Trip::where('driver_id', $user->id)
            ->with('passenger')
            ->latest()
            ->take(10) // Solo los últimos 10
            ->get();

        return Inertia::render('Dashboard', [
            'trips' => $myTrips,           // Para su historial
            'availableTrips' => $availableTrips, // Para que acepte nuevos
            'userRole' => 'driver'
        ]);
    }

    // --- CASO 3: PASAJERO (Default) ---
    // Solo ve SUS viajes
    $trips = \App\Models\Trip::where('passenger_id', $user->id)
        ->with('driver') // Traemos datos del chofer (si ya aceptó)
        ->latest()
        ->take(5)
        ->get();

    return Inertia::render('Dashboard', [
        'trips' => $trips,
        'userRole' => 'passenger'
    ]);

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
}); 

    // NUEVA RUTA: Aceptar viaje
    Route::put('/trip/{id}/accept', [TripController::class, 'accept'])->name('trip.accept');
    // --- RUTAS DE VIAJES ---
    // Solicitar
    Route::get('/request-ride', [TripController::class, 'create'])->name('trip.create');
    Route::post('/request-ride', [TripController::class, 'store'])->name('trip.store');
    
    // Gestión del Chofer
    Route::put('/trip/{id}/accept', [TripController::class, 'accept'])->name('trip.accept');
    
    // NUEVAS: Iniciar y Finalizar
    Route::put('/trip/{id}/start', [TripController::class, 'startTrip'])->name('trip.start');
    Route::put('/trip/{id}/finish', [TripController::class, 'finishTrip'])->name('trip.finish');giy

// --- RUTAS DE ADMINISTRADOR (NUEVAS) ---
Route::middleware(['auth'])->group(function () {
    // Ver la lista de pendientes
    Route::get('/admin/drivers', [AdminController::class, 'index'])->name('admin.drivers');
    
    // Aprobar a uno específico
    Route::put('/admin/drivers/{id}/approve', [AdminController::class, 'approve'])->name('admin.approve');
});


// --- FINAL DEL ARCHIVO ---
require __DIR__.'/auth.php';