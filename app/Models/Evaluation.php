<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'status',
        'self_total_score',
        'manager_total_score',
        'hr_total_score',
        'final_score',
        'grade_id',
        'self_note',
        'manager_note',
        'hr_note',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function grade()
    {
        return $this->belongsTo(EvaluationGrade::class);
    }

    public function details()
    {
        return $this->hasMany(EvaluationDetail::class);
    }
}
