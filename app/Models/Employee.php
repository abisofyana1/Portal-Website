<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employee';

    protected $fillable = [
        'npp',
        'name',
        'is_active',
        'email',
        'last_login',
        'created_by_id',
        'updated_by_id',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function roles()
    {
        return $this->hasMany(EmployeeRole::class);
    }
}
