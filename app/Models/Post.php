<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Post extends Model
{
    use HasFactory;

    public const TYPE_COURSE = 'course';
    public const TYPE_PRODUCT = 'product';
    public const TYPE_NEWS = 'news';

    protected $fillable = [
        'type',
        'category_id',
        'seller_id',
        'title',
        'slug',
        'seo_title',
        'seo_description',
        'summary',
        'sales_policy',
        'content',
        'what_to_learn',
        'course_includes',
        'course_requirements',
        'address',
        'province_id',
        'ward_id',
        'map_embed',
        'location_image',
        'area',
        'area_from',
        'area_to',
        'floor_count',
        'floor_count_from',
        'floor_count_to',
        'unit_count',
        'unit_count_from',
        'unit_count_to',
        'bedroom_count',
        'bedroom_count_from',
        'bedroom_count_to',
        'bathroom_count',
        'bathroom_count_from',
        'bathroom_count_to',
        'image',
        'pdf_file',
        'price',
        'is_active',
        'is_featured',
        'published_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'area' => 'decimal:2',
        'area_from' => 'decimal:2',
        'area_to' => 'decimal:2',
        'price' => 'decimal:2',
        'published_at' => 'datetime',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_COURSE => 'Course',
            self::TYPE_NEWS => 'News',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function galleryImages()
    {
        return $this->hasMany(PostImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function floorPlans()
    {
        $relation = $this->hasMany(PostFloorPlan::class)->orderBy('sort_order')->orderBy('id');

        if (! Schema::hasTable('post_floor_plans')) {
            $relation->getQuery()->from('post_images as post_floor_plans');
            $relation->whereRaw('1 = 0');
        }

        return $relation;
    }

    public function apartments()
    {
        return $this->hasMany(Apartment::class, 'project_id')->latest();
    }

    public function customerInquiries()
    {
        return $this->hasMany(CustomerInquiry::class)->latest();
    }

    public function getPdfUrlAttribute(): string
    {
        if ($this->pdf_file && file_exists(public_path(ltrim($this->pdf_file, '/')))) {
            return asset(ltrim($this->pdf_file, '/'));
        }

        return '';
    }

}
