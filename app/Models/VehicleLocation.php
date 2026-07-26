<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'trip_id',
        'latitude',
        'longitude',
        'speed',
        'heading',
        'municipality_id',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'speed' => 'decimal:2',
        'heading' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    /**
     * Relación con el conductor
     */
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * Relación con el viaje (si la ubicación fue durante un viaje activo)
     */
    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id');
    }

    /**
     * Relación con el municipio por el que transita
     */
    public function municipality()
    {
        return $this->belongsTo(Municipality::class, 'municipality_id');
    }
}
