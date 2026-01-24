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
            'role' => 'required|string|in:passenger,driver', // Solo permite estos dos roles
            
            // --- VALIDACIÓN DE CONDUCTOR ---
            // "required_if:role,driver" significa: Solo obligar si el rol es conductor
            'vehicle_model' => 'nullable|required_if:role,driver|string|max:255',
            'vehicle_plate' => 'nullable|required_if:role,driver|string|max:20',
            'vehicle_year' => 'nullable|required_if:role,driver|numeric',
            'license_file' => 'nullable|required_if:role,driver|file|mimes:jpg,jpeg,png,pdf|max:5120', // Max 5MB
        ]);

        // 1. Manejo del Archivo (Licencia)
        $licensePath = null;
        if ($request->hasFile('license_file')) {
            // Guarda el archivo en storage/app/public/licenses
            $licensePath = $request->file('license_file')->store('licenses', 'public');
        }

        // 2. Crear el Usuario con todos los datos
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            
            // Campos nuevos (se guardarán como NULL si es pasajero)
            'vehicle_model' => $request->vehicle_model,
            'vehicle_plate' => $request->vehicle_plate,
            'vehicle_year' => $request->vehicle_year,
            'license_file' => $licensePath, // Aquí guardamos la ruta de la imagen
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}