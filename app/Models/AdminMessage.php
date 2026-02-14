<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'admin_id',
        'subject',
        'body',
        'is_read',
    ];

    /**
     * El usuario que recibe el mensaje.
     */
    public function recipient()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * El administrador que envía el mensaje (opcional).
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
