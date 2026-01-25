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

   // 1. MODIFICAR: Cuando el pasajero pide el viaje
    public function store(Request $request)
    {
        $request->validate([
            'origin_lat' => 'required',
            'origin_long' => 'required',
            'dest_lat' => 'required',
            'dest_long' => 'required',
            'distance' => 'required',
            'fare' => 'required',
        ]);

        $trip = Trip::create([
            'passenger_id' => auth()->id(),
            'driver_id' => null,       // <--- IMPORTANTE: Nace sin chofer
            'origin_lat' => $request->origin_lat,
            'origin_long' => $request->origin_long,
            'dest_lat' => $request->dest_lat,
            'dest_long' => $request->dest_long,
            'distance' => $request->distance,
            'fare' => $request->fare,
            'status' => 'pending',     // <--- Nace en espera
        ]);

        // Redirigimos al Dashboard con un mensaje (usando Flash de Inertia si lo tienes config, sino no pasa nada)
        return redirect()->route('dashboard')->with('message', 'Buscando conductor...');
    }

    // 2. NUEVO: Cuando el conductor acepta el viaje
    public function accept($id)
    {
        $trip = Trip::findOrFail($id);

        // Seguridad: Solo un conductor puede aceptar y solo si el viaje está pendiente
        if (auth()->user()->role !== 'driver') {
            abort(403, 'Solo los conductores pueden aceptar viajes.');
        }

        if ($trip->status !== 'pending') {
            return back()->with('error', 'Este viaje ya fue tomado por otro conductor.');
        }

        // Asignamos el viaje a ESTE conductor
        $trip->update([
            'driver_id' => auth()->id(),
            'status' => 'accepted' // O 'in_progress' si prefieres que arranque de una vez
        ]);

        return back(); // Recargamos para que vea que ya es suyo
    }
    // 3. INICIAR EL VIAJE (El pasajero subió al carro)
    public function startTrip($id)
    {
        $trip = Trip::findOrFail($id);

        // Seguridad: Solo el chofer asignado puede iniciarlo
        if (auth()->id() !== $trip->driver_id) {
            abort(403, 'No tienes permiso para iniciar este viaje.');
        }

        $trip->update([
            'status' => 'in_progress'
        ]);

        return back();
    }

    // 4. FINALIZAR Y COBRAR (Llegaron al destino)
    public function finishTrip($id)
    {
        $trip = Trip::findOrFail($id);

        if (auth()->id() !== $trip->driver_id) {
            abort(403, 'No tienes permiso para finalizar este viaje.');
        }

        // Aquí "simulamos" que el pago se procesó exitosamente
        $trip->update([
            'status' => 'completed',
            // Si tuvieras una columna 'payment_status', aquí pondrías 'paid'
        ]);

        return back(); 
    }
}