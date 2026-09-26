<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationCriterion extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'max_score',
        'order',
        'is_active',
    ];

    public function parent()
    {
        return $this->belongsTo(EvaluationCriterion::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(EvaluationCriterion::class, 'parent_id')->orderBy('order', 'asc');
    }
}
