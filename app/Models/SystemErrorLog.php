<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemErrorLog extends Model
{
    protected $fillable = [
        'user_id',
        'username',
        'error_class',
        'error_message',
        'file',
        'line',
        'trace',
        'explanation',
        'suggestion',
        'url',
        'method',
        'ip_address',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
