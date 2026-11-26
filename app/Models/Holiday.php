<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'holiday_date',
        'name',
        'type',
        'work_value',
        'note',
    ];

    protected $casts = [
        'work_value' => 'float',
    ];
}
