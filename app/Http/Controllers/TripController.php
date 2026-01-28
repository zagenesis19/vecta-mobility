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
        // Mantenemos tus reglas, pero aceptamos 'price' (que viene del front nuevo) o 'amount'
        $request->validate([
            'origin' => 'required|string', // Viene del Vue como 'origin'
            'destination' => 'required|string',
            'origin_lat' => 'required|numeric',
            'origin_lng' => 'required|numeric',
            'destination_lat' => 'required|numeric',
            'destination_lng' => 'required|numeric',
            'payment_method' => 'required|string', 
            // Quitamos la restricción estricta 'in:cash...' para evitar errores si el Vue manda "Efectivo"
        ]);

        // 2. CREACIÓN (EL PUENTE)
        // Aquí hacemos el mapeo: Vue (origin) -> BD (origin_address)
        Trip::create([
            'passenger_id' => Auth::id(),
            
            // Mapeo de nombres normalizados
            'origin_address' => $request->origin, 
            'destination_address' => $request->destination,
            
            // Coordenadas
            'origin_lat' => $request->origin_lat,
            'origin_lng' => $request->origin_lng,
            'destination_lat' => $request->destination_lat,
            'destination_lng' => $request->destination_lng,
            
            // Precio (Usamos price si viene, o amount, o 5.00 por defecto)
            'price' => $request->price ?? $request->amount ?? 5.00,
            
            'payment_method' => $request->payment_method,
            'status' => 'pending',
        ]);

        // Redirigimos al Dashboard para ver la tarjeta del viaje creado
        return redirect()->route('dashboard')->with('success', '¡Viaje solicitado con éxito!');
    }

    // --- FASE 3: ACEPTAR Y GESTIONAR VIAJE (Lógica Original Intacta) ---
    
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

        // Usamos route('dashboard') para asegurar que se refresquen las tarjetas
        return redirect()->route('dashboard')->with('success', 'Has aceptado el viaje. ¡Ve a recoger al pasajero!');
    }

    public function startTrip($id)
    {
        $trip = Trip::findOrFail($id);
        
        // Validación de seguridad
        if (Auth::id() !== $trip->driver_id) {
            return back()->with('error', 'No estás autorizado.');
        }

        $trip->update(['status' => 'in_progress']);
        return redirect()->route('dashboard')->with('success', 'Viaje iniciado. ¡Conduce con cuidado!');
    }

    // --- FASE 4: FINALIZACIÓN Y PAGO (Lógica Original Intacta) ---

    public function finishTrip($id)
    {
        $trip = Trip::findOrFail($id);

        if (Auth::id() !== $trip->driver_id) {
            return back()->with('error', 'No estás autorizado.');
        }

        $trip->update(['status' => 'completed']);
        
        // Al terminar, redirigimos con success para que el Frontend muestre el Modal de Cobro
        return redirect()->route('dashboard')->with('success', 'Viaje finalizado. Procede al cobro.');
    }

    public function cancel($id)
    {
        $trip = Trip::findOrFail($id);
        $user = Auth::user();

        // Lógica de cancelación segura (Tuya)
        if ($user->role === 'passenger' && $trip->passenger_id === $user->id) {
            $trip->delete();
            return back()->with('success', 'Viaje eliminado.');
        }
        elseif ($user->role === 'driver' && $trip->driver_id === $user->id) {
            // Si el chofer cancela, liberamos el viaje para otro
            $trip->update(['driver_id' => null, 'status' => 'pending']);
            return back()->with('success', 'Viaje liberado.');
        }
        
        return back()->with('error', 'No se pudo cancelar el viaje.');
    }
    
    // Método extra para actualizar estatus genérico (si lo usas en el futuro)
    public function updateStatus(Request $request, Trip $trip)
    {
        $trip->update(['status' => $request->status]);
        return back();
    }
}