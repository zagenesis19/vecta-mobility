<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /**
     * Guarda o actualiza una calificación (Estrellas + Comentario).
     */
    public function store(Request $request, Trip $trip)
    {
        // 1. Validar que nos manden datos correctos
        $request->validate([
            'rating' => 'required|integer|min:1|max:5', // Del 1 al 5
            'comment' => 'nullable|string|max:255',     // Comentario opcional
        ]);

        $user = Auth::user();

        // 2. Seguridad: Verificar que el usuario pertenece al viaje
        if ($user->id != $trip->driver_id && $user->id != $trip->passenger_id) {
            return back()->withErrors(['error' => 'No puedes calificar un viaje que no es tuyo.']);
        }

        // 3. Determinar a quién estamos calificando (Usamos == para evitar líos de tipos string vs int)
        $targetUserId = ($user->id == $trip->driver_id) ? $trip->passenger_id : $trip->driver_id;

        // 3.1 Validación extra: Si no hay un usuario destino (ej: viaje sin conductor)
        if (!$targetUserId) {
            return back()->withErrors(['error' => 'No se puede calificar este viaje porque no tiene un conductor o pasajero asignado.']);
        }

        // 4. EVITAR DUPLICADOS (Solución al error SQLSTATE[23000])
        Review::updateOrCreate(
            [
                'trip_id' => $trip->id,
                'reviewer_id' => $user->id,
            ],
            [
                'reviewed_id' => $targetUserId,
                'rating' => $request->rating,
                'comment' => $request->comment
            ]
        );

        // 5. Volver atrás con mensaje de éxito
        return back()->with('success', '¡Calificación guardada correctamente! ⭐');
    }
}