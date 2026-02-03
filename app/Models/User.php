<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * IMPORTANTE: Agregamos esto para que el "average_rating" viaje siempre a Vue.
     */
    protected $appends = ['average_rating'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_approved',
        'license_file', 

        // --- FASE 5: IDENTIDAD ---
        'phone_number',
        'phone_verified_at',
        'id_card_number',
        'birth_date',
        'id_card_photo_path',
        'identity_status',
        'identity_feedback',
        'profile_photo_path',
        'biometric_photo_path',
        
        // 🔥 GPS EN VIVO (Agregado en Paso 2)
        'current_lat',
        'current_lng',
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
        'password' => 'hashed',
        'phone_verified_at' => 'datetime',
        'is_approved' => 'boolean',
        'current_lat' => 'decimal:7',
        'current_lng' => 'decimal:7',
    ];

    // 🔥 RELACIÓN: Un usuario tiene un vehículo
    public function vehicle()
    {
        return $this->hasOne(Vehicle::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SISTEMA DE CALIFICACIONES (ESTRELLAS ⭐)
    |--------------------------------------------------------------------------
    */

    // Relación: Opiniones que ha recibido este usuario (sea chofer o pasajero)
    public function reviewsReceived()
    {
        return $this->hasMany(Review::class, 'reviewed_id');
    }

    // Atributo Calculado: Promedio de estrellas (Ej: 4.8)
    public function getAverageRatingAttribute()
    {
        // Si no tiene reviews, le damos 5.0 por defecto para animarlo
        return round($this->reviewsReceived()->avg('rating') ?: 5.0, 1);
    }
}