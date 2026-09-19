<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apartment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'content',
        'price',
        'area',
        'bedroom_count',
        'bathroom_count',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'area' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Post::class, 'project_id');
    }

    public function images()
    {
        return $this->hasMany(ApartmentImage::class)->orderBy('sort_order')->orderBy('id');
    }
}
