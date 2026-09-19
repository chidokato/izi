<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'target',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const TARGET_SELF = '_self';
    public const TARGET_BLANK = '_blank';

    public static function targets(): array
    {
        return [
            self::TARGET_SELF => 'Cùng thẻ (_self)',
            self::TARGET_BLANK => 'Thẻ mới (_blank)',
        ];
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('name');
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

    public function activeChildren()
    {
        return $this->children()->where('is_active', true);
    }

    public function activeChildrenTree()
    {
        return $this->activeChildren()->with('activeChildrenTree');
    }

    public function getActiveChildrenTreeAttribute()
    {
        if (! $this->relationLoaded('activeChildrenTree')) {
            $this->load('activeChildrenTree');
        }

        return $this->getRelationValue('activeChildrenTree');
    }

    public function getUrlAttribute(): string
    {
        $slug = trim((string) $this->slug);

        if ($slug === '' || $slug === '/') {
            return url('/');
        }

        if (Str::startsWith($slug, ['http://', 'https://', '#', 'mailto:', 'tel:', 'javascript:'])) {
            return $slug;
        }

        return url('/' . ltrim($slug, '/'));
    }
}
