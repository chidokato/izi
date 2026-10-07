<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_code',
        'name',
        'email',
        'phone',
        'department_id',
        'manager_id',
        'manager_l2_id',
        'position',
        'level',
        'join_date',
        'leave_date',
        'status',
        'annual_leave_balance',
    ];

    public function requests()
    {
        return $this->hasMany(AttendanceRequest::class);
    }

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function managerL2()
    {
        return $this->belongsTo(Employee::class, 'manager_l2_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
