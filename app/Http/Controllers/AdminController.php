<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | SECCIÓN 1: GESTIÓN DE IDENTIDAD (FASE 5)
    |--------------------------------------------------------------------------
    | Métodos encargados de verificar documentos (Cédula, Fotos, Licencia).
    */

    /**
     * Muestra la lista de usuarios con documentos pendientes de revisión.
     */
    public function verifications()
    {
        $users = User::where('identity_status', 'pending')
                     ->orderBy('updated_at', 'asc')
                     ->get();

        return Inertia::render('Admin/Verifications', [
            'users' => $users
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
    | Métodos recuperados para corregir el error "Method approve does not exist".
    | Estos métodos son llamados desde el Dashboard principal de Admin.
    */

    /**
     * Listado general de choferes (Dashboard Admin).
     */
    public function index()
    {
        // Traemos todos los choferes para gestionarlos
        $drivers = User::where('role', 'driver')
                       ->latest()
                       ->get();

        return Inertia::render('Admin/Drivers', [
            'drivers' => $drivers
        ]);
    }

    /**
     * Aprobar manualmente a un chofer (Botón simple del Dashboard).
     * ESTE ES EL MÉTODO QUE FALTABA Y CAUSABA EL ERROR.
     */
    public function approve($id)
    {
        $user = User::findOrFail($id);
        
        $user->update([
            'is_approved' => true,
            'identity_status' => 'approved' // Sincronizamos estado
        ]);
        
        return back()->with('success', 'Conductor aprobado manualmente.');
    }

    /**
     * Rechazar/Eliminar a un chofer (Botón simple del Dashboard).
     */
    public function reject($id)
    {
        $user = User::findOrFail($id);
        
        // Opción: Lo devolvemos a estado no aprobado
        $user->update(['is_approved' => false]);

        return back()->with('success', 'Conductor desaprobado.');
    }
}