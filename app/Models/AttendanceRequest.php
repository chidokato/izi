<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'employee_id',
        'type',
        'start_date',
        'end_date',
        'start_session',
        'end_session',
        'reason',
        'attachment',
        'requested_checkin',
        'requested_checkout',
        'status',
        'current_approval_step',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'requested_checkin' => 'datetime',
        'requested_checkout' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function requestApprovals()
    {
        return $this->hasMany(RequestApproval::class, 'request_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->code)) {
                $model->code = 'REQ-' . date('Ym') . '-' . strtoupper(uniqid());
            }
        });
    }
}
