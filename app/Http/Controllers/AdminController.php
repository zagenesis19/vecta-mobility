<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    // Muestra la lista de conductores pendientes
    public function index()
    {
        // Buscamos usuarios que sean 'driver' Y que NO estén aprobados
        $pendingDrivers = User::where('role', 'driver')
            ->where('is_approved', false)
            ->latest()
            ->get();

        return Inertia::render('Admin/DriversList', [
            'drivers' => $pendingDrivers
        ]);
    }

    // Aprueba a un conductor
    public function approve($id)
    {
        $driver = User::findOrFail($id);
        
        // Cambiamos el switch a VERDADERO
        $driver->update([
            'is_approved' => true
        ]);

        // Recargamos la página para ver que desaparece de la lista
        return back();
    }
}
