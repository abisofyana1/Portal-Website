<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $table = 'unit';

    protected $fillable = [
        'parent_id',
        'type',
        'code',
        'short_name',
        'name',
        'address',
        'is_active',
        'created_by_id',
        'updated_by_id',
    ];

    public function parent()
    {
        return $this->belongsTo(Unit::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Unit::class, 'parent_id');
    }

    public function employeeRoles()
    {
        return $this->hasMany(EmployeeRole::class);
    }
}
