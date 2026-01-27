<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    /**
     * Muestra la lista de usuarios pendientes de verificación.
     */
    public function verifications()
    {
        // Traemos solo los que están "pending" (o podrías traer todos para historial)
        $users = User::where('identity_status', 'pending')
                     ->orderBy('updated_at', 'asc') // Los más viejos primero
                     ->get();

        return Inertia::render('Admin/Verifications', [
            'users' => $users
        ]);
    }

    /**
     * Aprobar identidad.
     */
    public function approveIdentity(User $user)
    {
        $user->update([
            'identity_status' => 'approved',
            'identity_feedback' => null, // Limpiar feedback anterior si hubo
            'is_approved' => true // (Opcional) Si esto también activa al chofer
        ]);

        return back()->with('success', 'Usuario verificado correctamente.');
    }

    /**
     * Rechazar identidad con feedback.
     */
    public function rejectIdentity(Request $request, User $user)
    {
        $request->validate([
            'reason' => 'required|string|max:255'
        ]);

        $user->update([
            'identity_status' => 'rejected',
            'identity_feedback' => $request->reason,
            'is_approved' => false
        ]);

        return back()->with('success', 'Solicitud rechazada.');
    }
}