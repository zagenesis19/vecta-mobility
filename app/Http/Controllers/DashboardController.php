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

            return Inertia::render('Dashboard', [
                'trips' => $myTrips, // Mantengo esto por si algo lo usa, pero myTrips es más explícito
                'myTrips' => $myTrips,
                'availableTrips' => $availableTrips,
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

        // Lógica corregida para "Viaje Actual":
        // 1. O es un viaje activo (pending, accepted, in_progress)
        // 2. O es un viaje completado QUE EL USUARIO AÚN NO HA CALIFICADO.
        $currentTrip = Trip::where('passenger_id', $user->id)
            ->whereNull('cancelled_at') // Ninguno cancelado
            ->where(function ($query) use ($user) {
                
                // Grupo A: Viajes Activos
                $query->whereIn('status', ['pending', 'accepted', 'in_progress'])
                
                // Grupo B: Viajes Completados sin reseña del usuario
                      ->orWhere(function ($q) use ($user) {
                          $q->where('status', 'completed')
                            ->whereDoesntHave('reviews', function ($sq) use ($user) {
                                $sq->where('reviewer_id', $user->id);
                            });
                      });
            })
            ->with('driver')
            ->latest()
            ->first();

        return Inertia::render('Dashboard', [
            'trips' => $trips,       
            'currentTrip' => $currentTrip, 
            'userRole' => 'passenger'
        ]);
    }
}
