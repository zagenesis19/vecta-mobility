<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Services\PricingService; // Inyectado

class TripController extends Controller
{
    protected $pricingService;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }
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

        // 2. CÁLCULO DE PRECIO (Backend Authority)
        $distance = $this->pricingService->calculateDistance(
            $request->origin_lat, 
            $request->origin_lng, 
            $request->destination_lat, 
            $request->destination_lng
        );
        // Estimación de tiempo (30km/h promedio en ciudad) -> 0.5 km/min
        $duration = $distance * 2; 

        $pricing = $this->pricingService->calculatePrice(
            $distance, 
            $duration, 
            $request->vehicle_type ?? 'car'
        );

        $passenger = Auth::user();

        // 3. CREACIÓN CON SNAPSHOT (Denormalización)
        Trip::create([
            'passenger_id' => $passenger->id,
            'origin_address' => $request->origin, 
            'destination_address' => $request->destination,
            'origin_lat' => $request->origin_lat,
            'origin_lng' => $request->origin_lng,
            'destination_lat' => $request->destination_lat,
            'destination_lng' => $request->destination_lng,
            'price' => $pricing['total'], // Precio calculado por el backend
            'payment_method' => $request->payment_method,
            'status' => 'pending',
            'vehicle_type' => $request->vehicle_type ?? 'car', 
            
            // Snapshots Pasajero
            'passenger_snapshot_name' => $passenger->name,
            'passenger_snapshot_phone' => $passenger->phone_number,
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

        $driver = Auth::user();
        
        // Cargar vehículo si no está cargado
        if (!$driver->relationLoaded('vehicle')) {
            $driver->load('vehicle');
        }

        $trip->update([
            'driver_id' => $driver->id,
            'status' => 'accepted',
            'accepted_at' => now(), // Importante para métricas de tiempo de espera

            // Snapshots Conductor & Vehículo
            'driver_snapshot_name' => $driver->name,
            'driver_snapshot_phone' => $driver->phone_number,
            'driver_snapshot_photo' => $driver->profile_photo_path,
            'vehicle_snapshot_data' => $driver->vehicle ? json_encode([
                'type' => $driver->vehicle->type,
                'model' => $driver->vehicle->model,
                'plate' => $driver->vehicle->plate,
                'color' => $driver->vehicle->color,
                'year' => $driver->vehicle->year,
            ]) : null,
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

    // Nuevo método: Rechazar viaje (Conductor)
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $trip = Trip::findOrFail($id);
        
        // Verificar que esté pendiente (no aceptado por otro)
        if ($trip->status !== 'pending') {
             return back()->with('error', 'El viaje ya no está disponible.');
        }

        // Simplemente registramos el rechazo en analytics (o una tabla de rechazos)
        // Y el viaje sigue 'pending' para otros conductores.
        // PERO el usuario pidió: "rechace una solicitud... motivo".
        // Si el conductor rechaza, ¿se cancela el viaje? No, debería pasar a otro conductor.
        // Si es una asignación directa, sí. Pero aquí es un pool de "Disponibles".
        // Si es un pool, "Rechazar" significa "Ocultar de mi lista".
        // O si el sistema asignó uno a uno (no parece ser el caso, es 'availableTrips').
        
        // ASUMIMOS LÓGICA DE NEGOCIO:
        // El conductor marca "Rechazar" para indicar por qué no lo toma (feedback analytics).
        // El viaje sigue disponible para otros.
        
        // Guardamos el evento de rechazo en la tabla de analytics (usando el controller de analytics o DB directa)
        // O creamos una relación, pero para simplificar según instrucciones:
        // "analiticas... motivos de rechazo del conductor".
        
        \DB::table('analytics_events')->insert([
            'user_id' => Auth::id(),
            'session_id' => request()->session()->getId(), // 🔥 Fix: Added missing session_id
            'event_type' => 'driver_rejection',
            'target' => $trip->id,
            'meta' => json_encode(['reason' => $request->reason, 'trip_id' => $trip->id]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Has rechazado la solicitud.');
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

    // 🔥 RASTREO GPS EN VIVO (Baja Latencia: Redis + Job Asíncrono para Histórico)
    public function updateLocation(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'speed' => 'nullable|numeric',
            'heading' => 'nullable|numeric',
            'trip_id' => 'nullable|integer',
            'municipality_id' => 'nullable|integer',
        ]);

        $user = Auth::user();

        if ($user->role === 'driver') {
            $lat = (float) $request->lat;
            $lng = (float) $request->lng;
            $speed = $request->speed ? (float) $request->speed : null;
            $heading = $request->heading ? (float) $request->heading : null;
            $tripId = $request->trip_id ? (int) $request->trip_id : null;
            $municipalityId = $request->municipality_id ? (int) $request->municipality_id : null;

            // 1. Guardar estado actual en Caché (Baja latencia en memoria RAM, < 1ms)
            \Illuminate\Support\Facades\Cache::put("driver:{$user->id}:current_location", [
                'driver_id' => $user->id,
                'lat' => $lat,
                'lng' => $lng,
                'speed' => $speed,
                'heading' => $heading,
                'updated_at' => now()->toIso8601String(),
            ], now()->addMinutes(15));

            // Intentar almacenar en Redis Geospatial si Redis está configurado
            try {
                if (class_exists('Illuminate\Support\Facades\Redis')) {
                    \Illuminate\Support\Facades\Redis::geoadd("drivers:locations", $lng, $lat, $user->id);
                }
            } catch (\Throwable $e) {
                // Fallback a Cache si Redis no está activo localmente
            }

            // 2. Persistir de forma asíncrona a la tabla histórica (vehicle_locations) vía Job en Cola
            \App\Jobs\ProcessLocationUpdateJob::dispatch(
                $user->id,
                $lat,
                $lng,
                $speed,
                $heading,
                $tripId,
                $municipalityId,
                now()->toDateTimeString()
            );

            return response()->json(['status' => 'ubicación actualizada', 'cached' => true]);
        }

        return response()->json(['status' => 'error', 'message' => 'No eres conductor'], 403);
    }
}