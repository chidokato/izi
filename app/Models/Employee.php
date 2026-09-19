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
        'department_id',
        'status',
    ];

    public function requests()
    {
        return $this->hasMany(AttendanceRequest::class);
    }
}
