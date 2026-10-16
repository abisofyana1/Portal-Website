<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsBank extends Model
{
    protected $table = 'news_bank';

    protected $fillable = [
        'title',
        'category',
        'keyword',
        'content',
        'is_active',
        'created_name',
        'updated_name',
        'category_id',
        'start_time',
        'end_time',
        'flag',
        'ref_id',
        'view_count',
        'created_by_id',
        'updated_by_id',
    ];
}
