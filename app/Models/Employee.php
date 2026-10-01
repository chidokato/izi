<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

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
}
