<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    // --- ESTA ES LA LÍNEA QUE TE FALTA ---
    // Le dice a Laravel: "Confía en mí, deja pasar todos los datos (latitud, longitud, precio, etc)"
    protected $guarded = [];

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