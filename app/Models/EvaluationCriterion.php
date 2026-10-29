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
        'target_types',
        'order',
        'is_active',
    ];

    protected $casts = [
        'target_types' => 'array',
        'is_active' => 'boolean',
    ];

    public static function getTargetTypes(): array
    {
        return [
            'manager' => 'Quản lý',
            'office' => 'Nhân viên văn phòng',
            'sale' => 'Nhân viên Sale',
            'collaborator' => 'Cộng tác viên (CTV)',
        ];
    }

    public function appliesTo(?string $type): bool
    {
        if (empty($type) || $type === 'all') {
            return true;
        }

        // Tiêu chí con phải nằm trong tiêu chí cha: cha phải áp dụng cho $type
        if ($this->parent_id && $this->parent) {
            if (!$this->parent->appliesTo($type)) {
                return false;
            }
        }

        $targets = $this->target_types;
        if (empty($targets) || in_array('all', $targets)) {
            return true;
        }

        return in_array($type, $targets);
    }

    public function getTargetTypeNamesAttribute(): array
    {
        $allTypes = self::getTargetTypes();
        $targets = $this->target_types;

        if (empty($targets) || in_array('all', $targets)) {
            if ($this->parent_id && $this->parent && !empty($this->parent->target_types) && !in_array('all', $this->parent->target_types)) {
                return $this->parent->target_type_names;
            }
            return ['all' => 'Tất cả'];
        }

        $names = [];
        foreach ($targets as $key) {
            if (isset($allTypes[$key])) {
                $names[$key] = $allTypes[$key];
            }
        }

        return $names;
    }

    public function parent()
    {
        return $this->belongsTo(EvaluationCriterion::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(EvaluationCriterion::class, 'parent_id')->orderBy('order', 'asc');
    }
}
