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
        // Validamos incluyendo el método de pago
        $request->validate([
            'origin' => 'required|string',
            'origin_lat' => 'required|numeric',
            'origin_lng' => 'required|numeric',
            'destination' => 'required|string',
            'destination_lat' => 'required|numeric',
            'destination_lng' => 'required|numeric',
            'distance' => 'nullable|numeric',
            'amount' => 'nullable|numeric',
            'payment_method' => 'required|string|in:cash,pago_movil' // <-- Importante para Fase 4
        ]);

        Trip::create([
            'passenger_id' => Auth::id(),
            'origin' => $request->origin,
            'origin_lat' => $request->origin_lat,
            'origin_lng' => $request->origin_lng,
            'destination' => $request->destination,
            'destination_lat' => $request->destination_lat,
            'destination_lng' => $request->destination_lng,
            'distance' => $request->distance ?? 0,
            'price' => $request->amount ?? 5.00, // Precio base o calculado
            'payment_method' => $request->payment_method,
            'status' => 'pending',
        ]);

        return redirect()->route('dashboard')->with('success', '¡Viaje solicitado con éxito!');
    }

    // --- FASE 3: ACEPTAR Y GESTIONAR VIAJE ---
    
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

        return Redirect::back()->with('success', 'Has aceptado el viaje. ¡Ve a recoger al pasajero!');
    }

    public function startTrip($id)
    {
        $trip = Trip::findOrFail($id);
        
        // Validación de seguridad: Solo el chofer asignado puede iniciar
        if (Auth::id() !== $trip->driver_id) {
            return back()->with('error', 'No estás autorizado.');
        }

        $trip->update(['status' => 'in_progress']);
        return Redirect::back()->with('success', 'Viaje iniciado. ¡Conduce con cuidado!');
    }

    // --- FASE 4: FINALIZACIÓN Y PAGO ---

    public function finishTrip($id)
    {
        $trip = Trip::findOrFail($id);

        if (Auth::id() !== $trip->driver_id) {
            return back()->with('error', 'No estás autorizado.');
        }

        $trip->update(['status' => 'completed']);
        
        // Aquí Argenis disparaba el modal de cobro en el front
        return Redirect::back()->with('success', 'Viaje finalizado. Procede al cobro.');
    }

    public function cancel($id)
    {
        $trip = Trip::findOrFail($id);
        $user = Auth::user();

        if ($user->role === 'passenger' && $trip->passenger_id === $user->id) {
            $trip->delete();
            return back()->with('success', 'Viaje eliminado.');
        }
        elseif ($user->role === 'driver' && $trip->driver_id === $user->id) {
            $trip->update(['driver_id' => null, 'status' => 'pending']);
            return back()->with('success', 'Viaje liberado.');
        }
        
        return back()->with('error', 'No se pudo cancelar el viaje.');
    }
}