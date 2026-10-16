<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $fillable = [
        'title',
        'keyword',
        'category',
        'start_time',
        'end_time',
        'headline',
        'content',
        'attachment',
        'attachments',
        'target_unit',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];
}
