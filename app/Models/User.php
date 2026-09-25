<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'job_title',
        'bio',
        'address',
        'phone',
        'secondary_phone',
        'whatsapp_phone',
        'avatar',
        'permission',
        'is_active',
        'employee_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function isAdmin(): bool
    {
        return in_array($this->permission, [1, 2]);
    }

    public function isExternalUser(): bool
    {
        return $this->permission == 3;
    }

    public function getPermissionLabelAttribute(): string
    {
        $permissions = [
            1 => 'Super Admin',
            2 => 'Admin',
            3 => 'User',
        ];
        return $permissions[$this->permission] ?? 'User';
    }
}
