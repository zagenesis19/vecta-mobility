<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class TripController extends Controller
{
    // --- 🧠 CEREBRO MATEMÁTICO (Fórmula de Haversine) ---
    // Esta función calcula la distancia exacta en Km entre dos coordenadas
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Radio de la Tierra en km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c; // Devuelve distancia en KM
    }

    // 1. MOSTRAR EL FORMULARIO (GET)
    public function create()
    {
        return Inertia::render('Trips/Create');
    }

    // 2. GUARDAR EL VIAJE (POST) - AHORA CON PRECIO REAL 💸
    public function store(Request $request)
    {
        // A. Validar que no envíen campos vacíos y que vengan las COORDENADAS
        $request->validate([
            'origin' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            // Validamos que el frontend nos mande los números del GPS
            'origin_lat' => 'required|numeric',
            'origin_lng' => 'required|numeric',
            'destination_lat' => 'required|numeric',
            'destination_lng' => 'required|numeric',
        ]);

        // B. Calcular la distancia usando la función matemática
        $distanceKm = $this->calculateDistance(
            $request->origin_lat,
            $request->origin_lng,
            $request->destination_lat,
            $request->destination_lng
        );

        // C. Definir Tarifas (Configuración estilo Ridery)
        $baseFare = 1.00;      // Tarifa mínima por arrancar ($1.00)
        $costPerKm = 0.50;     // Costo por cada Kilómetro ($0.50)
        
        // Fórmula: Base + (Km * Costo)
        $estimatedPrice = $baseFare + ($distanceKm * $costPerKm);
        
        // Redondear a 2 decimales y asegurar que nunca cueste menos de $1.50
        $finalPrice = max(1.50, round($estimatedPrice, 2));

        // D. Crear el viaje en la Base de Datos
        Trip::create([
            'passenger_id' => Auth::id(), 
            'origin' => $request->origin,
            'destination' => $request->destination,
            'status' => 'pending',        
            'price' => $finalPrice,       // 🔥 AQUÍ GUARDAMOS EL PRECIO REAL
            'driver_id' => null,          
        ]);

        // E. Redirigir al Dashboard
        return redirect()->route('dashboard')->with('message', '¡Solicitud enviada! Precio estimado: $' . $finalPrice);
    }

    // --- FUNCIONES PARA EL CONDUCTOR (INTACTAS) ---

    public function accept($id)
    {
        $trip = Trip::findOrFail($id);
        
        // Solo aceptar si está pendiente
        if ($trip->status !== 'pending') {
            return back()->withErrors(['error' => 'Este viaje ya no está disponible.']);
        }

        $trip->update([
            'driver_id' => Auth::id(),
            'status' => 'accepted'
        ]);

        return back();
    }

    public function startTrip($id)
    {
        $trip = Trip::findOrFail($id);
        if ($trip->driver_id !== Auth::id()) abort(403);

        $trip->update(['status' => 'in_progress']);
        return back();
    }

    public function finishTrip($id)
    {
        $trip = Trip::findOrFail($id);
        if ($trip->driver_id !== Auth::id()) abort(403);

        $trip->update(['status' => 'completed']);
        return back();
    }

    // --- FUNCIÓN: CANCELAR / LIBERAR VIAJE (INTACTA) ---
    public function cancel($id)
    {
        $trip = Trip::findOrFail($id);
        $user = Auth::user();

        // CASO A: Si el PASAJERO cancela -> Se borra el viaje
        if ($user->role === 'passenger' && $trip->passenger_id === $user->id) {
            $trip->delete();
        }
        // CASO B: Si el CONDUCTOR cancela -> Libera el viaje para otro
        elseif ($user->role === 'driver' && $trip->driver_id === $user->id) {
            $trip->update([
                'driver_id' => null,
                'status' => 'pending'
            ]);
        }

        return back();
    }
}