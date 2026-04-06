<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage; 
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        // 🔥 MODIFICADO: Buscamos las reseñas recibidas para mostrarlas en el perfil
        $reviews = $request->user()->reviewsReceived()
            ->with('reviewer:id,name') // Traemos solo el nombre de quien calificó
            ->latest()
            ->get();

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'reviews' => $reviews, // <--- Enviamos las reseñas a la vista (Vue)
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // --- LÓGICA DE FOTO DE PERFIL (Bypassa el identity_status) ---
        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_photo_path);
            }
            $user->profile_photo_path = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        $user->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Update the user's IDENTITY information (Fase 5).
     */
    public function updateIdentity(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        
        // Bloqueo de seguridad: Si está aprobado, no puede cambiar documentos sensibles
        if ($user->identity_status === 'approved') {
             return \Illuminate\Support\Facades\Redirect::back()
                 ->withErrors(['msg' => 'Tu identidad ya está verificada y no se puede editar.']);
        }

        $validated = $request->validate([
            'phone_number' => ['nullable', 'string', 'max:20'],
            'id_card_number' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],          
            'id_card_expires_at' => ['nullable', 'date'],
            'id_card_photo' => ['nullable', 'image', 'max:5120'],
            'biometric_photo' => ['nullable', 'string'],
        ]);

        // 1. Procesar Teléfono
        if (isset($validated['phone_number'])) {
            $phoneNumber = preg_replace('/[^0-9]/', '', $validated['phone_number']);
            $user->phone_number = strlen($phoneNumber) > 10 ? substr($phoneNumber, -10) : $phoneNumber;
        }
        
        // 2. Procesar Datos de Identidad
        if (isset($validated['id_card_number'])) $user->id_card_number = $validated['id_card_number'];
        if (isset($validated['birth_date'])) $user->birth_date = $validated['birth_date'];
        if (isset($validated['id_card_expires_at'])) $user->id_card_expires_at = $validated['id_card_expires_at'];

        // 3. Manejo de Documentos
        if ($request->hasFile('id_card_photo')) {
            if ($user->id_card_photo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->id_card_photo_path);
            }
            $user->id_card_photo_path = $request->file('id_card_photo')->store('id-cards', 'public');
        }
        
        if (!empty($validated['biometric_photo'])) {
            $image_parts = explode(";base64,", $validated['biometric_photo']);
            $image_base64 = count($image_parts) > 1 ? base64_decode($image_parts[1]) : base64_decode($validated['biometric_photo']);
            
            $fileName = 'biometrics/' . $user->id . '_' . time() . '.png';
            \Illuminate\Support\Facades\Storage::disk('secure')->put($fileName, $image_base64);
            
            if ($user->biometric_photo_path) {
                \Illuminate\Support\Facades\Storage::disk('secure')->delete($user->biometric_photo_path);
            }
            $user->biometric_photo_path = $fileName;
        }

        // Cambiar a pending si se subieron documentos sensibles
        if ($request->hasFile('id_card_photo') || !empty($validated['biometric_photo'])) {
            $user->identity_status = 'pending';
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'identity-updated');
    }

    /**
     * Subida de documentos del conductor (Dashboard - Modal)
     */
    public function updateDriverDocuments(Request $request): RedirectResponse
    {
        $request->validate([
            'profile_photo' => 'nullable|image|max:5120',
            'license_file' => 'nullable|image|max:5120',
            'id_card_photo' => 'nullable|image|max:5120',
            'medical_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'rif_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'circulation_permit' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // 🔥 Nuevo
        ]);

        $user = $request->user();

        // Helper para subir y reemplazar — solo actualiza si el store() fue exitoso
        $upload = function($field, $folder, $dbCol) use ($request, $user) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);

                // Guardar primero → solo borrar el viejo si el nuevo se guardó bien
                $newPath = $file->store($folder, 'secure');

                if ($newPath) {                               // store() retornó ruta válida
                    if ($user->$dbCol) {
                        Storage::disk('secure')->delete($user->$dbCol);
                    }
                    $user->$dbCol = $newPath;
                }
                // Si store() devuelve false → no tocamos el campo (evita borrar el path guardado)
            }
        };

        // Profile Photo → disco público (necesita ser visible en el avatar)
        if ($request->hasFile('profile_photo')) {
            $newPhoto = $request->file('profile_photo')->store('profile-photos', 'public');
            if ($newPhoto) {
                if ($user->profile_photo_path) Storage::disk('public')->delete($user->profile_photo_path);
                $user->profile_photo_path = $newPhoto;
            }
        }

        $upload('license_file',          'licenses',              'license_file');
        $upload('id_card_photo',          'id-cards',              'id_card_photo_path');
        $upload('medical_certificate',    'medical-certificates',  'medical_certificate_file');
        $upload('rif_file',               'rifs',                  'rif_file');
        $upload('circulation_permit',     'circulation-permits',   'circulation_permit_file_path');


        // Si completó todo, cambiamos estatus a pending para que el admin revise
        // 🔥 ACTUALIZADO: Debe incluir circulation_permit_file_path
        if ($user->profile_photo_path && $user->license_file && $user->id_card_photo_path && $user->medical_certificate_file && $user->rif_file && $user->circulation_permit_file_path) {
            $user->identity_status = 'pending';
        }

        $user->save();

        return Redirect::back()->with('status', 'documents-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}