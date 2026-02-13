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
    // 1. Obtener todos los municipios de la BD (para asegurar que existen en el objeto final)
    $municipalities = \App\Models\Municipality::all();

    // 2. Inicializar stats con 0 para todos los municipios
    $driverStats = [];
    foreach ($municipalities as $municipality) {
        if ($municipality->name) {
            $driverStats[$municipality->name] = 0;
        }
    }

    // 3. Consultar conteos reales agrupados por municipality_id (nuevos registros con FK)
    $counts = User::where('role', 'driver')
        ->where('is_approved', true)
        ->whereNotNull('municipality_id')
        ->select('municipality_id', \DB::raw('count(*) as count'))
        ->groupBy('municipality_id')
        ->get();

    foreach ($counts as $count) {
        $muni = $municipalities->find($count->municipality_id);
        if ($muni && $muni->name) {
            $driverStats[$muni->name] += $count->count;
        }
    }

    // 4. Soporte legacy: conductores que tienen el string 'municipality' pero NO tienen municipality_id
    $legacyDrivers = User::where('role', 'driver')
        ->where('is_approved', true)
        ->whereNull('municipality_id')
        ->whereNotNull('municipality')
        ->get();

    foreach ($legacyDrivers as $driver) {
        $legacyValue = $driver->municipality; // e.g. "Cristóbal Rojas (Charallave)"
        // Intentar hacer match con el nombre del municipio o la capital
        foreach ($municipalities as $muni) {
            if (
                str_contains($legacyValue, $muni->name) || 
                ($muni->capital && str_contains($legacyValue, $muni->capital))
            ) {
                $driverStats[$muni->name]++;
                break;
            }
        }
    }

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'driverStats' => $driverStats,
    ]);
});

// Store Simulation Routes
Route::get('/app-store', function () {
    return Inertia::render('Stores/AppStore'); 
})->name('app-store');

Route::get('/google-play', function () {
    return Inertia::render('Stores/PlayStore'); 
})->name('google-play');

// Social Media Simulations
Route::get('/instagram', function () {
    return Inertia::render('Social/InstagramProfile'); 
})->name('social.instagram');

// 2. DASHBOARD INTELIGENTE
Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

// 2.1 Analytics Public Route (Store Events)
Route::post('/analytics/events', [App\Http\Controllers\Admin\AnalyticsController::class, 'store'])->name('analytics.store');

// 3. GRUPO DE RUTAS AUTENTICADAS
Route::middleware('auth')->group(function () {
    
    // Analytics Routes
    Route::get('/admin/analytics/stats', [App\Http\Controllers\Admin\AnalyticsController::class, 'stats'])->name('admin.analytics.stats');
    Route::get('/admin/analytics', function () {
        return \Inertia\Inertia::render('Admin/AnalyticsDashboard');
    })->name('admin.analytics');

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/identity', [ProfileController::class, 'updateIdentity'])->name('profile.identity.update');
    Route::post('/driver/documents', [ProfileController::class, 'updateDriverDocuments'])->name('driver.documents.update');
    
    Route::get('/request-ride', [TripController::class, 'create'])->name('trips.create');
    Route::post('/request-ride', [TripController::class, 'store'])->name('trips.store');
    Route::delete('/trip/{trip}/cancel', [TripController::class, 'cancel'])->name('trip.cancel');
    
    Route::put('/trip/{id}/accept', [TripController::class, 'accept'])->name('trip.accept');
    Route::put('/trip/{id}/start', [TripController::class, 'startTrip'])->name('trips.start');
    Route::put('/trip/{id}/finish', [TripController::class, 'finishTrip'])->name('trips.finish');
    Route::post('/driver/location', [TripController::class, 'updateLocation'])->name('driver.location');

    // Nuevas rutas para cancelación con motivo, historial y pago
    Route::post('/trip/{id}/cancel-with-reason', [TripController::class, 'cancelWithReason'])->name('trip.cancelWithReason');
    Route::get('/trip-history', [TripController::class, 'history'])->name('trip.history');
    Route::post('/trip/{id}/confirm-payment', [TripController::class, 'confirmPayment'])->name('trip.confirmPayment');

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

Route::get('/simulate-movement', function () {
    $driver = User::where('role', 'driver')->first();
    if (!$driver) return "No driver found";

    // Simulate random movement
    $lat = 10.2443 + (rand(-100, 100) / 10000);
    $lng = -66.8622 + (rand(-100, 100) / 10000);
    
    // Dispatch event
    event(new \App\Events\DriverLocationUpdated($driver->id, $driver->municipality_id, ['lat' => $lat, 'lng' => $lng]));
    
    return "Event Dispatched: Lat $lat, Lng $lng";
});

require __DIR__.'/auth.php';