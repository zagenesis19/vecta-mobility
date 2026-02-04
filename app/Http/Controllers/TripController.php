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

        $trip->update([
            'status' => 'in_progress',
            'started_at' => now()
        ]);
        return redirect()->route('dashboard')->with('success', 'Viaje iniciado. ¡Conduce con cuidado!');
    }

    // --- FINALIZACIÓN Y PAGO ---

    public function finishTrip($id)
    {
        $trip = Trip::findOrFail($id);

        if (Auth::id() !== $trip->driver_id) {
            return back()->with('error', 'No estás autorizado.');
        }

        // Calcular duración si hay started_at
        $durationMinutes = null;
        if ($trip->started_at) {
            $durationMinutes = now()->diffInMinutes($trip->started_at);
        }

        $trip->update([
            'status' => 'completed',
            'finished_at' => now(),
            'duration_minutes' => $durationMinutes
        ]);
        return redirect()->route('dashboard')->with('success', 'Viaje finalizado. Procede al cobro.');
    }

    public function cancel($id)
    {
        $trip = Trip::findOrFail($id);
        $user = Auth::user();

        // Solo permitir cancelación si el viaje está en pending o accepted
        if (!in_array($trip->status, ['pending', 'accepted'])) {
            return back()->with('error', 'No se puede cancelar un viaje en curso o completado.');
        }

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

    // Nuevo método: Cancelar con motivo
    public function cancelWithReason(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $trip = Trip::findOrFail($id);
        $user = Auth::user();

        // Solo permitir cancelación si el viaje está en pending o accepted
        if (!in_array($trip->status, ['pending', 'accepted'])) {
            return back()->with('error', 'No se puede cancelar un viaje en curso o completado.');
        }

        // Determinar quién cancela
        $cancelledBy = $user->role === 'passenger' ? 'passenger' : 'driver';

        // Actualizar el viaje con el motivo de cancelación
        $trip->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->reason,
            'cancelled_by' => $cancelledBy,
            'cancelled_at' => now(),
        ]);

        return redirect()->route('dashboard')->with('success', 'Viaje cancelado.');
    }

    // Nuevo método: Obtener historial de viajes
    public function history()
    {
        $user = Auth::user();
        
        if ($user->role === 'passenger') {
            $trips = Trip::where('passenger_id', $user->id)
                ->whereIn('status', ['completed', 'cancelled'])
                ->with(['driver'])
                ->orderBy('updated_at', 'desc')
                ->get();
        } else {
            $trips = Trip::where('driver_id', $user->id)
                ->whereIn('status', ['completed', 'cancelled'])
                ->with(['passenger'])
                ->orderBy('updated_at', 'desc')
                ->get();
        }

        return Inertia::render('TripHistory', [
            'trips' => $trips,
            'userRole' => $user->role,
        ]);
    }

    // Nuevo método: Confirmar pago móvil
    public function confirmPayment($id)
    {
        $trip = Trip::findOrFail($id);
        $user = Auth::user();

        // Solo el conductor puede confirmar el pago
        if ($user->role !== 'driver' || $trip->driver_id !== $user->id) {
            return back()->with('error', 'No estás autorizado.');
        }

        $trip->update([
            'payment_confirmed' => true,
            'payment_confirmed_at' => now(),
        ]);

        return back()->with('success', 'Pago confirmado.');
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