<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    // Estos son los campos de la tabla 'vehicles'
    protected $fillable = [
        'user_id', // Para saber de quién es
        'type',    // 'car' o 'motorcycle'
        'model',
        'plate',
        'year',
        'color',
        'photo_path' // Opcional
    ];

    // Relación inversa: Un vehículo pertenece a un usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}