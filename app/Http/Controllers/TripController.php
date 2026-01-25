<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class TripController extends Controller
{
    // 1. MOSTRAR EL FORMULARIO (GET)
    public function create()
    {
        return Inertia::render('Trips/Create');
    }

    // 2. GUARDAR EL VIAJE (POST)
    public function store(Request $request)
    {
        // A. Validar que no envíen campos vacíos
        $request->validate([
            'origin' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
        ]);

        // B. Crear el viaje en la Base de Datos
        Trip::create([
            'passenger_id' => Auth::id(), // El usuario conectado es el pasajero
            'origin' => $request->origin,
            'destination' => $request->destination,
            'status' => 'pending',        // Nace como "pendiente"
            'price' => rand(5, 20),       // Simulamos un precio (Fase 5)
            'driver_id' => null,          // Aún no tiene conductor
        ]);

        // C. Redirigir al Dashboard para ver la espera
        return redirect()->route('dashboard')->with('message', '¡Solicitud enviada!');
    }

    // --- FUNCIONES QUE YA TENÍAS (PARA EL CONDUCTOR) ---

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
}