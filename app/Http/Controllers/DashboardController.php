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
            $trips = Trip::with(['passenger', 'driver'])->latest()->get(); 
            
            // 🔥 Obtener lat/lng de conductores para el Mapa Térmico
            $driverLocations = User::where('role', 'driver')
                ->whereNotNull('current_lat')
                ->whereNotNull('current_lng')
                ->get(['current_lat', 'current_lng'])
                ->map(function($driver) {
                    return [(float)$driver->current_lat, (float)$driver->current_lng];
                })->values()->all();
            
            return Inertia::render('Dashboard', [
                'trips' => $trips,
                'userRole' => 'admin',
                'driverLocations' => $driverLocations 
            ]);
        }

        // B. CONDUCTOR
        if ($user->role === 'driver') {
            $driverType = $user->vehicle ? $user->vehicle->type : 'car'; 

            $availableTrips = Trip::where('status', 'pending')
                ->whereNull('driver_id')
                ->where('passenger_id', '!=', $user->id)
                ->where('vehicle_type', $driverType)
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
            ->with('driver')
            ->latest()
            ->take(5)
            ->get();

        // Viajes Activos (Para la tarjeta principal)
        $currentTrip = Trip::where('passenger_id', $user->id)
            ->whereNull('cancelled_at')
            ->whereIn('status', ['pending', 'accepted', 'in_progress'])
            ->with(['driver', 'reviews'])
            ->latest()
            ->first();

        // 🔥 SEÑAL PARA MODALES: Viaje completado sin calificar
        $pendingActionTrip = Trip::where('passenger_id', $user->id)
            ->where('status', 'completed')
            ->whereDoesntHave('reviews', function($sq) use ($user) {
                $sq->where('reviewer_id', $user->id);
            })
            ->with('driver')
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
