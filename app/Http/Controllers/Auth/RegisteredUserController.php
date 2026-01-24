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
            'role' => 'required|string',
            // Validaciones nuevas (son 'nullable' porque el Pasajero no las envía)
            'car_model' => 'nullable|string|max:50',
            'license_plate' => 'nullable|string|max:20',
            'vehicle_year' => 'nullable|integer|min:1990|max:'.(date('Y')+1),
            'license_photo' => 'nullable|image|max:2048', // Máximo 2MB, solo imágenes
        ]);

        // 1. Manejo de la FOTO (Si subieron una)
        $photoPath = null;
        if ($request->hasFile('license_photo')) {
            // Guardar en la carpeta "licenses" dentro del disco "public"
            $photoPath = $request->file('license_photo')->store('licenses', 'public');
        }

        // 2. Crear el Usuario con TODOS los datos
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            // Datos del Conductor
            'car_model' => $request->car_model,
            'license_plate' => $request->license_plate,
            'vehicle_year' => $request->vehicle_year,
            'license_photo_path' => $photoPath,
            'is_approved' => false, // Por defecto nadie está aprobado
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
