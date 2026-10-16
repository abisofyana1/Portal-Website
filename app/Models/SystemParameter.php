<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemParameter extends Model
{
    protected $table = 'system_parameter';

    protected $fillable = [
        'key',
        'value',
        'title',
        'url',
    ];
}
