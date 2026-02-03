<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | SECCIÓN 1: GESTIÓN DE IDENTIDAD Y VERIFICACIONES (CENTRALIZADO)
    |--------------------------------------------------------------------------
    | Aquí gestionamos tanto los documentos como la aprobación inicial del chofer.
    */

    /**
     * Muestra la lista de verificaciones (Identidad + Solicitudes de Ingreso).
     */
    public function verifications()
    {
        // 1. Usuarios con documentos de identidad pendientes (Tu lógica original)
        $users = User::where('identity_status', 'pending')
                     ->orderBy('updated_at', 'asc')
                     ->get();

        // 2. NUEVO: Conductores registrados esperando aprobación básica (Movido del Dashboard)
        $pendingDrivers = User::where('role', 'driver')
                              ->where('is_approved', false)
                              ->get();

        return Inertia::render('Admin/Verifications', [
            'users' => $users,              // Para la pestaña de Documentos
            'pendingDrivers' => $pendingDrivers // Para la pestaña de Solicitudes Nuevas
        ]);
    }

    /**
     * Aprobar la identidad (Documentos válidos).
     */
    public function approveIdentity(User $user)
    {
        $user->update([
            'identity_status' => 'approved',
            'identity_feedback' => null,
            'is_approved' => true // Al verificar identidad, aprobamos al chofer automáticamente
        ]);

        return back()->with('success', 'Identidad verificada y conductor aprobado.');
    }

    /**
     * Rechazar la identidad (Documentos inválidos o borrosos).
     */
    public function rejectIdentity(Request $request, User $user)
    {
        $request->validate([
            'reason' => 'required|string|max:255'
        ]);

        $user->update([
            'identity_status' => 'rejected',
            'identity_feedback' => $request->reason,
            'is_approved' => false // Si la identidad falla, no puede trabajar
        ]);

        return back()->with('success', 'Solicitud rechazada correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | SECCIÓN 2: GESTIÓN GENERAL DE CHOFERES (CORE)
    |--------------------------------------------------------------------------
    */

    /**
     * Listado general de choferes (Dashboard Admin / Lista completa).
     */
    public function index()
    {
        $drivers = User::where('role', 'driver')
                        ->latest()
                        ->get();

        return Inertia::render('Admin/Drivers', [
            'drivers' => $drivers
        ]);
    }

    /**
     * Aprobar manualmente a un chofer (Desde la lista de Verificaciones).
     */
    public function approve($id)
    {
        $user = User::findOrFail($id);
        
        $user->update([
            'is_approved' => true,
            // Opcional: Si apruebas manualmente sin docs, puedes dejar identity en pending o approved según prefieras.
            // Aquí lo dejamos tal cual tu código anterior para no romper flujos.
            'identity_status' => 'approved' 
        ]);
        
        return back()->with('success', 'Conductor aprobado manualmente.');
    }

    /**
     * Rechazar solicitud de chofer nuevo.
     */
    public function reject($id)
    {
        $user = User::findOrFail($id);
        
        // CAMBIO IMPORTANTE: Usamos delete() para que desaparezca de la lista de "Pendientes".
        // Si solo ponemos is_approved=false, seguiría apareciendo en la lista eternamente.
        $user->delete();

        return back()->with('success', 'Solicitud rechazada y usuario eliminado.');
    }
}