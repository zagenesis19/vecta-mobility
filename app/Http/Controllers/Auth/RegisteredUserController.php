<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
// Asegúrate de que este modelo exista (si no lo importas arriba, úsalo con la barra invertida como hice abajo)
// use App\Models\Vehicle; 

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|string|in:passenger,driver',
            
            // --- VALIDACIONES DE VEHÍCULO ---
            // 1. Agregamos el TIPO (Moto o Carro)
            'vehicle_type'  => 'nullable|required_if:role,driver|string|in:car,motorcycle',
            
            // 2. Mantenemos tus validaciones originales
            'vehicle_model' => 'nullable|required_if:role,driver|string|max:255',
            'vehicle_plate' => 'nullable|required_if:role,driver|string|max:20',
            'vehicle_year'  => 'nullable|required_if:role,driver|integer|min:1990|max:'.(date('Y')+1),
            'vehicle_color' => 'nullable|required_if:role,driver|string|max:50', 
            
            // 3. Licencia (Mantenemos tu lógica de archivo)
            'license_file'  => 'nullable|image|max:5120', 
        ]);

        // 1. Manejo de la FOTO DE LICENCIA
        // Esto se queda igual, se guarda en el usuario
        $licensePath = null;
        if ($request->hasFile('license_file')) {
            $licensePath = $request->file('license_file')->store('licenses', 'public');
        }

        // 2. Crear el Usuario (DATOS PERSONALES SOLAMENTE)
        // 🔥 AQUÍ QUITAMOS LOS DATOS DEL VEHÍCULO DE ESTA TABLA
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_approved' => false, 
            'identity_status' => 'unverified', // Agregamos esto para Fase 5
            
            'license_file' => $licensePath, // La licencia sigue siendo del usuario
        ]);

        // 3. Crear el Vehículo (NUEVA TABLA)
        // 🔥 AQUÍ GUARDAMOS EL FIERRO APARTE
        if ($request->role === 'driver') {
            \App\Models\Vehicle::create([
                'user_id' => $user->id, // Vinculamos al dueño
                'type' => $request->vehicle_type, // 'car' o 'motorcycle'
                'model' => $request->vehicle_model,
                'plate' => $request->vehicle_plate,
                'year'  => $request->vehicle_year,
                'color' => $request->vehicle_color,
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}