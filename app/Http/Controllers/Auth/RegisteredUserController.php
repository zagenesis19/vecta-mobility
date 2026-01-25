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
            'role' => 'required|string|in:passenger,driver', // Validamos que sea uno de los dos
            
            // --- CORREGIDO: Usamos los nombres de TU base de datos ---
            'vehicle_model' => 'nullable|required_if:role,driver|string|max:255',
            'vehicle_plate' => 'nullable|required_if:role,driver|string|max:20',
            'vehicle_year'  => 'nullable|required_if:role,driver|integer|min:1990|max:'.(date('Y')+1),
            'license_file'  => 'nullable|required_if:role,driver|image|max:5120', // 5MB Max
        ]);

        // 1. Manejo de la FOTO (Si subieron una)
        $licensePath = null;
        
        // OJO: Tu formulario envía 'license_file', no 'license_photo'
        if ($request->hasFile('license_file')) {
            $licensePath = $request->file('license_file')->store('licenses', 'public');
        }

        // 2. Crear el Usuario con los nombres CORRECTOS de la Base de Datos
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_approved' => false, // Por defecto nadie está aprobado

            // Aquí estaba el error: cambiamos car_model por vehicle_model
            'vehicle_model' => $request->vehicle_model,
            'vehicle_plate' => $request->vehicle_plate,
            'vehicle_year'  => $request->vehicle_year,
            'license_file'  => $licensePath, 
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}