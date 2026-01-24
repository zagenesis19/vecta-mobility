<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TripController extends Controller
{
    public function create()
    {
        return Inertia::render('RequestRide');
    }

    public function store(Request $request)
    {
        // 1. Validar que vengan los datos
        $request->validate([
            'origin_lat' => 'required',
            'origin_long' => 'required',
            'dest_lat' => 'required',
            'dest_long' => 'required',
        ]);

        // 2. Tomar las coordenadas REALES del formulario
        $origin_lat = $request->origin_lat;
        $origin_long = $request->origin_long;
        $dest_lat = $request->dest_lat;
        $dest_long = $request->dest_long;

        // 3. CÁLCULO DE DISTANCIA (Fórmula de Haversine)
        $earthRadius = 6371; // Radio de la tierra en km

        $dLat = deg2rad($dest_lat - $origin_lat);
        $dLon = deg2rad($dest_long - $origin_long);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($origin_lat)) * cos(deg2rad($dest_lat)) *
             sin($dLon / 2) * sin($dLon / 2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        $distanciaLineal = $earthRadius * $c; // Distancia en línea recta
        $distancia = $distanciaLineal * 1.4;  // Corrección por tráfico/curvas (Factor Urbano)

        // 4. PRECIO
        $precio = 2.00 + ($distancia * 1.50);

        // 5. BUSCAR CONDUCTOR (Emparejamiento)
        // Buscamos un usuario que sea conductor y no sea yo mismo
        $driver = User::where('role', 'driver')
                    ->where('id', '!=', Auth::id())
                    ->inRandomOrder()
                    ->first();

        // 6. GUARDAR EL VIAJE (¡Aquí estaba el error antes!)
        Trip::create([
            'passenger_id' => Auth::id(),
            'driver_id' => $driver ? $driver->id : null, // Asigna conductor o NULL
            
            // ESTAS SON LAS LÍNEAS QUE FALTABAN:
            'origin_lat' => $origin_lat,
            'origin_long' => $origin_long,
            'dest_lat' => $dest_lat,
            'dest_long' => $dest_long,
            'distance' => round($distancia, 2),
            'fare' => round($precio, 2),
            'status' => 'active',
        ]);

        return redirect()->route('dashboard');
    }

    public function updateStatus(Request $request, Trip $trip)
    {
        $request->validate([
            'status' => 'required|in:completed,cancelled'
        ]);

        $trip->update([
            'status' => $request->status
        ]);

        return back();
    }
}