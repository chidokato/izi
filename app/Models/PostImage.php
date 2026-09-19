<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostImage extends Model
{
    use HasFactory;

    public const TYPE_INTERIOR = 'interior';
    public const TYPE_PERSPECTIVE = 'perspective';
    public const TYPE_AMENITY = 'amenity';

    protected $fillable = [
        'post_id',
        'image',
        'image_type',
        'sort_order',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_INTERIOR => 'Anh noi that',
            self::TYPE_PERSPECTIVE => 'Anh phoi canh',
            self::TYPE_AMENITY => 'Anh tien ich',
        ];
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
