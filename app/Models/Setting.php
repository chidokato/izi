<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'address',
        'email',
        'hotline',
        'social',
        'logo',
        'footer_logo',
        'favicon',
        'footer_column_1_title',
        'footer_column_1_content',
        'footer_column_2_title',
        'footer_column_2_content',
        'footer_column_3_title',
        'footer_column_3_content',
        'footer_column_4_title',
        'footer_column_4_content',
    ];

    protected $casts = [
        'social' => 'array',
    ];

    public function getLogoUrlAttribute(): string
    {
        return $this->imageUrl($this->logo, 'images/logo/logo-black.png');
    }

    public function getFooterLogoUrlAttribute(): string
    {
        return $this->imageUrl($this->footer_logo, 'images/logo/logo-black.png');
    }

    public function getFaviconUrlAttribute(): string
    {
        return $this->imageUrl($this->favicon, 'images/favicon.png');
    }

    protected function imageUrl(?string $path, string $fallback): string
    {
        if ($path) {
            return Str::startsWith($path, ['http://', 'https://']) ? $path : asset($path);
        }

        return asset($fallback);
    }
}
