<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    use HasFactory;

    protected $fillable = [
        'province_id',
        'code',
        'name',
        'type',
        'source_file',
    ];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }
}
