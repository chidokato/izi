<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerInquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'project_title',
        'name',
        'phone',
        'email',
        'source_url',
        'download_url',
        'ip_address',
        'user_agent',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
