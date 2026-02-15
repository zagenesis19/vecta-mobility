<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Municipality extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'capital',
        'description',
        'svg_path',
        'calibration_data',
    ];

    protected $casts = [
        'calibration_data' => 'array',
    ];
}
