<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeRole extends Model
{
    protected $table = 'employee_role';

    protected $fillable = [
        'employee_id',
        'unit_id',
        'role_id',
        'status_id',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function role()
    {
        return $this->belongsTo(Lookup::class, 'role_id');
    }
}
