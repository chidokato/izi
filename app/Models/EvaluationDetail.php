<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluation_id',
        'criteria_id',
        'self_score',
        'is_self_selected',
        'manager_score',
        'is_manager_selected',
        'hr_score',
        'is_hr_selected',
    ];

    protected $casts = [
        'is_self_selected' => 'boolean',
        'is_manager_selected' => 'boolean',
        'is_hr_selected' => 'boolean',
    ];

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function criterion()
    {
        return $this->belongsTo(EvaluationCriterion::class, 'criteria_id');
    }
}
