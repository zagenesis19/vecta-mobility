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
}