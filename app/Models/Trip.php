<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    // --- CAMBIO IMPORTANTE ---
    // En lugar de "dejar pasar todo", listamos explícitamente
    // qué columnas permitimos llenar. Esto es más seguro.
    protected $fillable = [
        'passenger_id',
        'driver_id',
        'origin',
        'destination',
        
        // 🔥 AQUÍ AGREGAMOS LAS COORDENADAS GPS NUEVAS
        'origin_lat',
        'origin_lng',
        'destination_lat',
        'destination_lng',

        'status',
        'price',
        'payment_method',
    ];

    // Relación con el Pasajero
    public function passenger()
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    // Relación con el Conductor
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}