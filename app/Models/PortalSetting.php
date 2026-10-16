<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortalSetting extends Model
{
    protected $fillable = [
        'section',
        'category',
        'title',
        'url',
        'image_name',
        'order_no',
        'is_active',
        'target_unit',
        'parent_id'
    ];

    public function children()
    {
        return $this->hasMany(PortalSetting::class, 'parent_id')->orderBy('order_no');
    }

    public function parent()
    {
        return $this->belongsTo(PortalSetting::class, 'parent_id');
    }

    protected $casts = [
        'is_active' => 'boolean',
        'target_unit' => 'array'
    ];
}
