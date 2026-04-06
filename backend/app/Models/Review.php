<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'reviewer_id',
        'reviewed_id',
        'rating',
        'comment'
    ];

    /**
     * 🔥 NUEVO: Relación para saber QUIÉN escribió la reseña.
     * Esto nos permite mostrar: "Juan Pérez te calificó con 5 estrellas".
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}