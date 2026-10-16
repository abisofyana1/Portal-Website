<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lookup extends Model
{
    protected $table = 'lookup';

    protected $fillable = [
        'type',
        'name',
        'value',
        'order_no',
        'created_by_id',
        'updated_by_id',
    ];
}
