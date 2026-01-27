<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail

{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // --- BÁSICOS (ESTOS ERAN LOS QUE FALTABAN) ---
        'name',
        'email',
        'password',
        
        // --- ROLES Y ESTADO ---
        'role',          // 'admin', 'driver', 'passenger'
        'is_approved',   // Para choferes
        
        // --- DATOS DE VEHÍCULO (Para Choferes) ---
        'vehicle_model',
        'vehicle_plate',
        'vehicle_year',
        'vehicle_color',
        'license_file',

        // --- IDENTIDAD Y SEGURIDAD (Fase 5) ---
        'phone_number',
        'phone_verified_at',
        'id_card_number',
        'birth_date',          // <--- Nuevo
        'id_card_expires_at',  // <--- Nuevo
        'id_card_photo_path',
        'profile_photo_path',
        'biometric_photo_path',
        
        // --- ESTADO DE VERIFICACIÓN (Admin) ---
        'identity_status',     // 'unverified', 'pending', 'approved', 'rejected'
        'identity_feedback',   // Razón del rechazo
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'id_card_expires_at' => 'date', // Importante para que Vue lo lea bien
        'password' => 'hashed',
        'is_approved' => 'boolean',
    ];
}