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
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Update the user's IDENTITY information (Fase 5).
     */
    public function updateIdentity(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        // Si ya está aprobado, NO DEJAR editar (Seguridad Backend)
        if ($user->identity_status === 'approved') {
             return Redirect::back()->withErrors(['msg' => 'Tu identidad ya está verificada y no se puede editar.']);
        }

        $validated = $request->validate([
            'phone_number' => ['nullable', 'string', 'max:20'],
            'id_card_number' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],          // <--- NUEVO
            'id_card_expires_at' => ['nullable', 'date'],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
            'id_card_photo' => ['nullable', 'image', 'max:5120'],
            'biometric_photo' => ['nullable', 'string'],
        ]);

        // Sanitizar Teléfono: Solo dejar los últimos 10 dígitos (quitar prefijos si vienen)
        $phoneNumber = preg_replace('/[^0-9]/', '', $validated['phone_number']);
        if (strlen($phoneNumber) > 10) {
            $phoneNumber = substr($phoneNumber, -10);
        }
        $user->phone_number = $phoneNumber;
        
        $user->id_card_number = $validated['id_card_number'];
        if (isset($validated['birth_date'])) $user->birth_date = $validated['birth_date']; // <--- NUEVO
        if (isset($validated['id_card_expires_at'])) $user->id_card_expires_at = $validated['id_card_expires_at'];

        // Manejo de Fotos (Igual que antes...)
        if ($request->hasFile('profile_photo')) {
            // ... (borrar old, guardar new)
            $user->profile_photo_path = $request->file('profile_photo')->store('profile-photos', 'public');
        }
        if ($request->hasFile('id_card_photo')) {
            $user->id_card_photo_path = $request->file('id_card_photo')->store('id-cards', 'public');
        }
        
        // Biometría: Decodificar Base64 y guardar como archivo
        if (!empty($validated['biometric_photo'])) {
            $image_parts = explode(";base64,", $validated['biometric_photo']);
            // Si viene con prefijo data:image/... lo quitamos, si no, asumimos raw base64
            $image_base64 = count($image_parts) > 1 ? base64_decode($image_parts[1]) : base64_decode($validated['biometric_photo']);
            
            $fileName = 'biometrics/' . $user->id . '_' . time() . '.png';
            
            Storage::disk('public')->put($fileName, $image_base64);
            
            $user->biometric_photo_path = $fileName;
        }

        // CAMBIAR ESTADO A "PENDING" AUTOMÁTICAMENTE
        // Si el usuario subió documentos, solicita verificación de nuevo
        $user->identity_status = 'pending';

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
        ]);

        $user = $request->user();

        // Helper para subir y reemplazar
        $upload = function($field, $folder, $dbCol) use ($request, $user) {
            if ($request->hasFile($field)) {
                if ($user->$dbCol) Storage::disk('public')->delete($user->$dbCol);
                $user->$dbCol = $request->file($field)->store($folder, 'public');
            }
        };

        $upload('profile_photo', 'profile-photos', 'profile_photo_path');
        $upload('license_file', 'licenses', 'license_file');
        $upload('id_card_photo', 'id-cards', 'id_card_photo_path');
        $upload('medical_certificate', 'medical-certificates', 'medical_certificate_file');
        $upload('rif_file', 'rifs', 'rif_file');

        // Si completó todo, cambiamos estatus a pending para que el admin revise
        if ($user->profile_photo_path && $user->license_file && $user->id_card_photo_path && $user->medical_certificate_file && $user->rif_file) {
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