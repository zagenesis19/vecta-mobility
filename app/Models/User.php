<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\AdminMessage;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * IMPORTANTE: Agregamos esto para que las estrellas viajen siempre a Vue.
     */
    protected $appends = ['average_rating', 'total_ratings'];

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
        'medical_certificate_file',
        'rif_file',
        'circulation_permit_file_path', // 🔥 Nuevo requisito
        
        'identity_status',
        'identity_feedback',
        'profile_photo_path',
        'biometric_photo_path',
        
        // 🔥 GPS EN VIVO
        'current_lat',
        'current_lng',

        // 🔥 NUEVOS CAMPOS DE PERFIL
        'gender',
        'terms_accepted',
        'country',
        'state',
        'state',
        'state',
        'municipality', // (Optional legacy field)
        'municipality_id', // 🔥 Clave foránea normalizada

        // 🔥 GESTIÓN DE USUARIOS (ADMIN)
        'is_active',
        'ban_reason',
    ];

    /**
     * Relación con Municipio (Normalizada)
     */
    public function municipalityRel()
    {
        return $this->belongsTo(Municipality::class, 'municipality_id');
    }

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
        'terms_accepted' => 'boolean',
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
        // PERO solo si queremos que aparezca algo. Si queremos que diga "Sin calificaciones" 
        // cuando no hay nada, deberíamos retornar null o 0.
        // El usuario dice que ve "Sin calificaciones", así que el código actual con 5.0 
        // debería mostrar 5.0. 
        // Investigando: si avg() es null, retorna 5.0.
        $avg = $this->reviewsReceived()->avg('rating');
        return $avg ? round($avg, 1) : 0; // Cambiamos a 0 si no hay para que el v-if detecte "sin calificaciones"
    }

    // Atributo Calculado: Total de calificaciones
    public function getTotalRatingsAttribute()
    {
        return $this->reviewsReceived()->count();
    }

    // 🔥 RELACIÓN: Mensajes administrativos recibidos por este usuario
    public function adminMessages()
    {
        return $this->hasMany(AdminMessage::class, 'user_id')->latest();
    }
}