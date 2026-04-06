<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    // --- LISTA DE PERMISOS (FILLABLE) ---
    // Aquí definimos qué columnas dejamos guardar.
    // Actualizado para coincidir con la migración normalizada.
    protected $fillable = [
        'passenger_id',
        'driver_id',
        
        // 🔥 CORRECCIÓN: Usamos los nombres nuevos de la BD
        'origin_address',      // Antes era 'origin'
        'destination_address', // Antes era 'destination'
        
        // Coordenadas GPS
        'origin_lat',
        'origin_lng',
        'destination_lat',
        'destination_lng',

        // Detalles
        'status',
        'price',
        'payment_method',
        'distance', // Agregado por si acaso, ya que está en la tabla
        'vehicle_type', // Tipo de vehículo solicitado
        
        // Campos de cancelación
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
        
        // Campos de confirmación de pago
        'payment_confirmed',
        'payment_confirmed_at',
        
        // Campos de duración del viaje
        'started_at',
        'finished_at',
        'duration_minutes',

        // Calificaciones
        'driver_rating',
        'driver_comment',
        'passenger_rating',
        'passenger_comment',
    ];

    protected $casts = [
        'payment_confirmed' => 'boolean',
        'cancelled_at' => 'datetime',
        'payment_confirmed_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    // Relación con el Pasajero (Lógica intacta)
    public function passenger()
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    // Relación con el Conductor (Lógica intacta)
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    // Relación con las Reseñas
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}