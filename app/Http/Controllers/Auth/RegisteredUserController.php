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
        // Sanitizar y limpiar datos antes de validar
        $idCardNumber = $this->sanitizeIdCard($request->id_card_number);
        $phoneNumber = $this->sanitizePhone($request->phone_number);
        
        // El frontend ya maneja el prefijo visualmente, guardamos solo los 10 dígitos locales
        $request->merge([
            'id_card_number' => $idCardNumber,
            'phone_number' => $phoneNumber,
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|string|in:passenger,driver',
            
            // --- VALIDACIONES DE VEHÍCULO ---
            'vehicle_type'  => 'nullable|required_if:role,driver|string|in:car,motorcycle',
            'vehicle_model' => 'nullable|required_if:role,driver|string|max:255',
            'vehicle_plate' => 'nullable|required_if:role,driver|string|max:20',
            'vehicle_year'  => 'nullable|required_if:role,driver|integer|min:1990|max:'.(date('Y')+1),
            'vehicle_color' => 'nullable|required_if:role,driver|string|max:50', 
            
            // 4. Nuevos campos de Identidad y Contacto - Mejorados
            'id_card_number' => 'required|string|min:6|max:10|regex:/^[0-9]+$/',
            // Volvemos a 10 dígitos para evitar el doble 58
            'phone_number' => 'required|string|min:10|max:10|regex:/^[0-9]+$/|unique:'.User::class,

            // 5. 🔥 NUEVOS CAMPOS REQUERIDOS
            'gender' => 'required|string|in:male,female,other',
            'terms_accepted' => 'accepted', // Debe ser true
            'country' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'municipality' => 'required|string|max:100',

            // 6. 📄 DOCUMENTOS (Solo Chofer)
            'profile_photo' => 'nullable|image|max:2048', // Opcional, o requerido si queremos obligar
            'license_file' => 'nullable|image|max:5120',
            'id_card_photo' => 'nullable|image|max:5120',
            'medical_certificate' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'rif_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // 1. Manejo de ARCHIVOS
        $licensePath = null;
        if ($request->hasFile('license_file')) {
            $licensePath = $request->file('license_file')->store('licenses', 'public');
        }

        $profilePhotoPath = null;
        if ($request->hasFile('profile_photo')) {
            $profilePhotoPath = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        $idCardPhotoPath = null;
        if ($request->hasFile('id_card_photo')) {
            $idCardPhotoPath = $request->file('id_card_photo')->store('id-cards', 'public');
        }

        $medicalPath = null;
        if ($request->hasFile('medical_certificate')) {
            $medicalPath = $request->file('medical_certificate')->store('medical-certificates', 'public');
        }

        $rifPath = null;
        if ($request->hasFile('rif_file')) {
            $rifPath = $request->file('rif_file')->store('rifs', 'public');
        }

        // 2. Crear el Usuario (DATOS PERSONALES SOLAMENTE)
        // 🔥 AQUÍ QUITAMOS LOS DATOS DEL VEHÍCULO DE ESTA TABLA
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_approved' => false, 
            'identity_status' => $request->role === 'driver' ? 'pending' : 'unverified', // Cambiado a pending para conductores
            
            // Documentos
            'license_file' => $licensePath,
            'profile_photo_path' => $profilePhotoPath,
            'id_card_photo_path' => $idCardPhotoPath,
            'medical_certificate_file' => $medicalPath,
            'rif_file' => $rifPath,

            'id_card_number' => $request->id_card_number,
            'phone_number' => $request->phone_number,

            // 🔥 NUEVOS CAMPOS GUARDADOS
            'gender' => $request->gender,
            'terms_accepted' => true,
            'country' => $request->country,
            'state' => $request->state,
            'municipality' => $request->municipality,
            'phone_verified_at' => now(), // ✅ Asumimos verificado si pasó el frontend
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

    /**
     * Valida el paso 1 del registro (unicidad)
     */
    public function validateStep(Request $request) {
        $idCardNumber = $this->sanitizeIdCard($request->id_card_number);
        $phoneNumber = $this->sanitizePhone($request->phone_number);
        
        $request->merge([
            'id_card_number' => $idCardNumber,
            'phone_number' => $phoneNumber,
        ]);

        $request->validate([
            'email' => 'required|string|email|max:255|unique:'.User::class,
            'id_card_number' => 'required|string|min:6|max:10|regex:/^[0-9]+$/',
            'phone_number' => 'required|string|min:10|max:10|regex:/^[0-9]+$/|unique:'.User::class,
        ]);

        return response()->json(['message' => 'Step 1 valid']);
    }

    /**
     * Sanitizar número de cédula
     * Elimina V-, E-, puntos, guiones y espacios
     */
    private function sanitizeIdCard($idCard)
    {
        // Convertir a string y a mayúsculas
        $idCard = strtoupper(trim($idCard));
        
        // Eliminar V-, E-, puntos, guiones, espacios
        $idCard = preg_replace('/[VE\-\.\s]/', '', $idCard);
        
        // Solo dejar números
        $idCard = preg_replace('/[^0-9]/', '', $idCard);
        
        return $idCard;
    }

    /**
     * Sanitizar número de teléfono
     * Elimina guiones, espacios, paréntesis
     */
    private function sanitizePhone($phone)
    {
        // Eliminar todo excepto números
        $phone = preg_replace('/[^0-9]/', '', trim($phone));
        
        return $phone;
    }
}