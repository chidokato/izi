<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyWorkSetting extends Model
{
    protected $fillable = ['month', 'standard_days', 'public_holidays', 'notes'];
}
