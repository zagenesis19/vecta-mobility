<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Trip;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // A. ADMINISTRADOR
    if ($user->role === 'admin' || $user->email === 'admin@vecta.com') {
        $recentTrips = Trip::with(['passenger', 'driver'])->latest()->take(10)->get(); 
        
        // 📊 Stats en tiempo real desde la BD
        $adminStats = [
            'total_trips' => Trip::count(),
            'completed_trips' => Trip::where('status', 'completed')->count(),
            'cancelled_trips' => Trip::where('status', 'cancelled')->count(),
            'active_trips' => Trip::whereIn('status', ['pending', 'accepted', 'in_progress'])->count(),
            'in_progress_trips' => Trip::where('status', 'in_progress')->count(),
            'total_drivers' => User::where('role', 'driver')->count(),
            'approved_drivers' => User::where('role', 'driver')->where('is_approved', true)->count(),
            'total_passengers' => User::where('role', 'passenger')->count(),
            'total_revenue' => Trip::where('status', 'completed')->sum('price'),
            'avg_trip_price' => round(Trip::where('status', 'completed')->avg('price') ?? 0, 2),
            'completion_rate' => Trip::count() > 0 
                ? round((Trip::where('status', 'completed')->count() / Trip::count()) * 100, 1) 
                : 0,
            'pending_verifications' => User::where('identity_status', 'pending')->count(),
        ];

        // 🔥 Mapa de Calor: coordenadas de viajes (origin + destination)
        $heatPoints = [];
        
        // 1) Conductores en línea
        $driverLocations = User::where('role', 'driver')
            ->whereNotNull('current_lat')
            ->whereNotNull('current_lng')
            ->get(['current_lat', 'current_lng'])
            ->map(fn($d) => [(float)$d->current_lat, (float)$d->current_lng, 0.8])
            ->values()->all();
        $heatPoints = array_merge($heatPoints, $driverLocations);

        // 2) Orígenes y destinos de viajes recientes (últimos 30 días)
        $tripCoords = Trip::where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('origin_lat')
            ->get(['origin_lat', 'origin_lng', 'destination_lat', 'destination_lng']);
        
        foreach ($tripCoords as $t) {
            if ($t->origin_lat && $t->origin_lng) {
                $heatPoints[] = [(float)$t->origin_lat, (float)$t->origin_lng, 0.6];
            }
            if ($t->destination_lat && $t->destination_lng) {
                $heatPoints[] = [(float)$t->destination_lat, (float)$t->destination_lng, 0.4];
            }
        }
        
        return Inertia::render('Dashboard', [
            'trips' => $recentTrips,
            'adminStats' => $adminStats,
            'userRole' => 'admin',
            'driverLocations' => $heatPoints,
        ]);
    }

        // B. CONDUCTOR
        if ($user->role === 'driver') {
            $driverType = $user->vehicle ? $user->vehicle->type : 'car'; 

            $availableTrips = Trip::where('status', 'pending')
                ->whereNull('driver_id')
                ->where('passenger_id', '!=', $user->id)
                // ->where('vehicle_type', $driverType) // 🔥 TEMPORAL: Ver todos los viajes para pruebas
                ->with('passenger')
                ->latest()
                ->get();

            $myTrips = Trip::where('driver_id', $user->id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->with('passenger')
                ->orderByRaw("CASE 
                    WHEN status = 'pending' THEN 1 
                    WHEN status = 'accepted' THEN 2 
                    WHEN status = 'in_progress' THEN 3 
                    ELSE 4 
                END")
                ->latest()
                ->take(10)
                ->get();

            // 🔥 SEÑAL PARA MODALES: Buscar último viaje completado sin cobrar o sin calificar
            $pendingActionTrip = Trip::where('driver_id', $user->id)
                ->where('status', 'completed')
                ->where(function($q) use ($user) {
                    $q->where('payment_confirmed', false)
                      ->orWhereDoesntHave('reviews', function($sq) use ($user) {
                          $sq->where('reviewer_id', $user->id);
                      });
                })
                ->with('passenger')
                ->latest()
                ->first();

            return Inertia::render('Dashboard', [
                'trips' => $myTrips,
                'myTrips' => $myTrips,
                'availableTrips' => $availableTrips,
                'pendingActionTrip' => $pendingActionTrip, // Nueva prop
                'userRole' => 'driver',
                'isApproved' => (bool) $user->is_approved, 
            ]);
        }
        
        // C. PASAJERO
        $trips = Trip::where('passenger_id', $user->id)
            ->with('driver.vehicle') // 🔥 Cargar vehículo
            ->latest()
            ->take(5)
            ->get();

        // Viajes Activos (Para la tarjeta principal)
        $currentTrip = Trip::where('passenger_id', $user->id)
            ->whereNull('cancelled_at')
            ->whereIn('status', ['pending', 'accepted', 'in_progress'])
            ->with(['driver.vehicle', 'reviews']) // 🔥 Cargar vehículo
            ->latest()
            ->first();

        // 🔥 SEÑAL PARA MODALES: Viaje completado sin calificar (evita bucles infinitos)
        $pendingActionTrip = Trip::where('passenger_id', $user->id)
            ->where('status', 'completed')
            ->whereNotNull('driver_id') // 🔥 SOLO si tiene conductor
            ->whereDoesntHave('reviews', function($sq) use ($user) {
                $sq->where('reviewer_id', $user->id);
            })
            ->with(['driver.vehicle', 'reviews'])
            ->latest()
            ->first();

        return Inertia::render('Dashboard', [
            'trips' => $trips,       
            'currentTrip' => $currentTrip, 
            'pendingActionTrip' => $pendingActionTrip, // Nueva prop
            'userRole' => 'passenger'
        ]);

        return Inertia::render('Dashboard', [
            'trips' => $trips,       
            'currentTrip' => $currentTrip, 
            'userRole' => 'passenger'
        ]);
    }
}
