<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TripController;
use App\Models\Trip;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
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

// 2. DASHBOARD INTELIGENTE (Detecta si eres Admin o Pasajero)

Route::get('/dashboard', function () {
    $user = Auth::user();
    $isAdmin = $user->email === 'admin@vecta.com';

    if ($isAdmin) {
        // --- VISTA DE JEFE (Todo) ---
        $trips = Trip::with(['passenger', 'driver'])->latest()->take(5)->get();
        
        // Datos para el mapa (Solo viajes completados)
        $heatmapData = Trip::select('origin_lat', 'origin_long')
                            ->where('status', 'completed')
                            ->get()
                            ->map(fn($t) => [$t->origin_lat, $t->origin_long]);

        $stats = [
            'earnings' => number_format(Trip::where('status', 'completed')->sum('fare'), 2),
            'active' => Trip::where('status', 'active')->count(),
            'cancelled' => Trip::where('status', 'cancelled')->count(),
            'completed' => Trip::where('status', 'completed')->count(),
        ];
    } else {
        // --- VISTA DE PASAJERO (Solo sus viajes) ---
        // ¡ESTA ES LA LÍNEA QUE FALTABA!
        $trips = Trip::where('passenger_id', $user->id)->latest()->take(5)->get();
        
        $heatmapData = []; // El pasajero no ve mapa de calor
        $stats = null;     // El pasajero no ve estadísticas
    }

    return Inertia::render('Dashboard', [
        'trips' => $trips,
        'stats' => $stats,
        'isAdmin' => $isAdmin,
        'heatmapData' => $heatmapData
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

// 3. RUTAS PRIVADAS (Pedir Taxi y Perfil)
Route::middleware('auth')->group(function () {
    // Rutas para pedir viaje
    Route::get('/request-ride', [TripController::class, 'create'])->name('trip.create');
    Route::post('/request-ride', [TripController::class, 'store'])->name('trip.store');

    // Rutas de perfil de usuario
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// 4. RUTAS DE AUTENTICACIÓN (LOGIN/LOGOUT) - ¡IMPORTANTE!
require __DIR__.'/auth.php';