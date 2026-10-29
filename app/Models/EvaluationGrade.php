<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'min_score',
        'max_score',
    ];

    protected $casts = [
        'min_score' => 'float',
        'max_score' => 'float',
    ];

    public static function getGradeForScore($score)
    {
        if ($score === null || $score === '') {
            return null;
        }

        $total = round((float)$score, 2);
        $grades = self::orderBy('min_score', 'desc')->get();
        if ($grades->isEmpty()) {
            return null;
        }

        // 1. Khớp chính xác trong khoảng [min, max] (cho phép dung sai 0.001)
        foreach ($grades as $g) {
            $min = (float)$g->min_score;
            $max = (float)$g->max_score;
            if ($total >= ($min - 0.001) && $total <= ($max + 0.001)) {
                return $g;
            }
        }

        // 2. Nếu nằm trong khoảng hở giữa 2 bậc (ví dụ 4.45 đến 4.50), lấy bậc tương ứng
        foreach ($grades as $g) {
            if ($total >= ((float)$g->min_score - 0.001)) {
                return $g;
            }
        }

        return $grades->last();
    }
}
