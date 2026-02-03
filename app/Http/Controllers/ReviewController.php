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
        if ($user->id !== $trip->driver_id && $user->id !== $trip->passenger_id) {
            return back()->withErrors(['error' => 'No puedes calificar un viaje que no es tuyo.']);
        }

        // 3. Determinar a quién estamos calificando
        $targetUserId = ($user->id === $trip->driver_id) ? $trip->passenger_id : $trip->driver_id;

        // 4. EVITAR DUPLICADOS (Solución al error SQLSTATE[23000])
        // Usamos updateOrCreate para que si ya existe la combinación viaje-usuario, solo se actualice
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