<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'parent_id',
        'name',
        'slug',
        'seo_title',
        'seo_description',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const TYPE_COURSE = 'course';
    public const TYPE_PRODUCT = 'product';
    public const TYPE_NEWS = 'news';

    public static function types(): array
    {
        return [
            self::TYPE_COURSE => 'Course',
            self::TYPE_NEWS => 'News',
        ];
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function childrenTree()
    {
        return $this->children()->with('childrenTree');
    }

    public function getChildrenTreeAttribute()
    {
        return $this->getRelationValue('childrenTree');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
