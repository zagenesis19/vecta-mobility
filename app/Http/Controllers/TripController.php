<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class TripController extends Controller
{
    public function create()
    {
        return Inertia::render('Trips/Create');
    }

    public function store(Request $request)
    {
        // 1. VALIDACIÓN
        $request->validate([
            'origin' => 'required|string', 
            'destination' => 'required|string',
            'origin_lat' => 'required|numeric',
            'origin_lng' => 'required|numeric',
            'destination_lat' => 'required|numeric',
            'destination_lng' => 'required|numeric',
            'payment_method' => 'required|string',
            'vehicle_type' => 'nullable|string', // Aceptamos vehicle_type (car/motorcycle)
        ]);

        // 2. CREACIÓN (EL PUENTE)
        Trip::create([
            'passenger_id' => Auth::id(),
            'origin_address' => $request->origin, 
            'destination_address' => $request->destination,
            'origin_lat' => $request->origin_lat,
            'origin_lng' => $request->origin_lng,
            'destination_lat' => $request->destination_lat,
            'destination_lng' => $request->destination_lng,
            'price' => $request->price ?? 5.00,
            'payment_method' => $request->payment_method,
            'status' => 'pending',
            'vehicle_type' => $request->vehicle_type ?? 'car', // Guardamos si pidió Moto o Carro
        ]);

        return redirect()->route('dashboard')->with('success', '¡Viaje solicitado con éxito!');
    }

    // --- ACEPTAR Y GESTIONAR VIAJE ---
    
    public function accept($id)
    {
        $trip = Trip::findOrFail($id);
        
        if ($trip->status !== 'pending') {
            return Redirect::back()->with('error', 'Este viaje ya no está disponible.');
        }

        $trip->update([
            'driver_id' => Auth::id(),
            'status' => 'accepted'
        ]);

        return redirect()->route('dashboard')->with('success', 'Has aceptado el viaje. ¡Ve a recoger al pasajero!');
    }

    public function startTrip($id)
    {
        $trip = Trip::findOrFail($id);
        
        if (Auth::id() !== $trip->driver_id) {
            return back()->with('error', 'No estás autorizado.');
        }

        $trip->update(['status' => 'in_progress']);
        return redirect()->route('dashboard')->with('success', 'Viaje iniciado. ¡Conduce con cuidado!');
    }

    // --- FINALIZACIÓN Y PAGO ---

    public function finishTrip($id)
    {
        $trip = Trip::findOrFail($id);

        if (Auth::id() !== $trip->driver_id) {
            return back()->with('error', 'No estás autorizado.');
        }

        $trip->update(['status' => 'completed']);
        return redirect()->route('dashboard')->with('success', 'Viaje finalizado. Procede al cobro.');
    }

    public function cancel($id)
    {
        $trip = Trip::findOrFail($id);
        $user = Auth::user();

        // 1. Si el Pasajero cancela -> Se borra el viaje
        if ($user->role === 'passenger' && $trip->passenger_id === $user->id) {
            $trip->delete();
            return back()->with('success', 'Viaje eliminado.');
        }
        // 2. Si el Chofer cancela -> Se libera el viaje (Re-emparejamiento)
        elseif ($user->role === 'driver' && $trip->driver_id === $user->id) {
            $trip->update(['driver_id' => null, 'status' => 'pending']);
            return back()->with('success', 'Viaje liberado.');
        }
        
        return back()->with('error', 'No se pudo cancelar el viaje.');
    }
    
    // Método extra para actualizar estatus genérico
    public function updateStatus(Request $request, Trip $trip)
    {
        $trip->update(['status' => $request->status]);
        return back();
    }

    // 🔥 NUEVO MÉTODO: RASTREO GPS EN VIVO (Paso 4 Completado)
    public function updateLocation(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $user = Auth::user();

        if ($user->role === 'driver') {
            $user->update([
                'current_lat' => $request->lat,
                'current_lng' => $request->lng
            ]);
            
            return response()->json(['status' => 'ubicación actualizada']);
        }

        return response()->json(['status' => 'error', 'message' => 'No eres conductor'], 403);
    }
}