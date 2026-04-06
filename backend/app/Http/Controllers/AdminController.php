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
        // 1. Usuarios con documentos de identidad pendientes
        $users = User::where('identity_status', 'pending')
            ->with('vehicle') // 🔥 Cargar vehículo para inspección
            ->orderBy('updated_at', 'asc')
            ->get();

        return Inertia::render('Admin/Verifications', [
            'users' => $users,              // Identidad (Docs)
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
            ->with('vehicle') // 🔥 Cargar datos del vehículo
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
    /*
    |--------------------------------------------------------------------------
    | SECCIÓN 3: GESTIÓN DE USUARIOS (SANCIONES Y MENSAJES)
    |--------------------------------------------------------------------------
    */

    /**
     * Listado de usuarios (Pasajeros y Conductores) para gestión avanzada.
     */
    public function users(Request $request)
    {
        $role = $request->input('role', 'passenger'); // 'passenger' or 'driver'
        $search = $request->input('search');

        $users = User::where('role', $role)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/UserManagement', [
            'users' => $users,
            'filters' => [
                'role' => $role,
                'search' => $search
            ]
        ]);
    }

    /**
     * Sancionar o Reactivar usuario (Toggle Status).
     */
    public function toggleStatus(Request $request, User $user)
    {
        $request->validate([
            'ban_reason' => 'nullable|string|max:500',
        ]);

        // Si está activo, lo desactivamos (ban)
        if ($user->is_active) {
            $user->update([
                'is_active' => false,
                'ban_reason' => $request->ban_reason ?? 'Sancionado por administración.',
            ]);
            $message = 'Usuario sancionado correctamente.';
        } else {
            // Si está inactivo, lo activamos
            $user->update([
                'is_active' => true,
                'ban_reason' => null,
            ]);
            $message = 'Usuario reactivado correctamente.';
        }

        return back()->with('success', $message);
    }

    /**
     * Enviar mensaje administrativo (Buzón interno).
     */
    public function sendMessage(Request $request, User $user)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        \App\Models\AdminMessage::create([
            'user_id' => $user->id,
            'admin_id' => auth()->id(),
            'subject' => 'Mensaje de Soporte Vecta',
            'body' => $request->message,
        ]);

        return back()->with('success', 'Mensaje enviado al usuario.');
    }
}