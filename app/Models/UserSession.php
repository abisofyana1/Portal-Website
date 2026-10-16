<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
    protected $table = 'user_session';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'last_activity',
        'ip_address',
        'user_agent',
        'payload',
        'info',
        'role_id',
        'unit_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
