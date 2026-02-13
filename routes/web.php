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
    // Consultar conductores aprobados agrupados por municipio
    $driversByMunicipality = User::where('role', 'driver')
        ->where('is_approved', true)
        ->select('municipality', \DB::raw('count(*) as count'))
        ->groupBy('municipality')
        ->pluck('count', 'municipality')
        ->toArray();
    
    // Asegurar que todos los municipios estén presentes (incluso con 0 conductores)
    $municipalities = [
        'Charallave' => 0,
        'Cúa' => 0,
        'Ocumare del Tuy' => 0,
        'San Francisco de Yare' => 0,
        'Santa Teresa del Tuy' => 0,
        'Santa Lucía del Tuy' => 0,
    ];
    
    // Combinar con los datos reales
    $driverStats = array_merge($municipalities, $driversByMunicipality);
    
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

// 2. DASHBOARD INTELIGENTE
Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

// 3. GRUPO DE RUTAS AUTENTICADAS
Route::middleware('auth')->group(function () {
    
    // Analytics Routes
    Route::post('/analytics/events', [App\Http\Controllers\Admin\AnalyticsController::class, 'store'])->name('analytics.store');
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

require __DIR__.'/auth.php';