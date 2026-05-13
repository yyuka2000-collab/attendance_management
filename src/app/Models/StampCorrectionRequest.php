<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StampCorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'user_id',
        'new_clock_in',
        'new_clock_out',
        'remarks',
        'status',      // 0: 承認待ち, 1: 承認済み
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'new_clock_in'  => 'datetime',
        'new_clock_out' => 'datetime',
        'approved_at'   => 'datetime',
    ];

    public function isPending(): bool
    {
        return $this->status === 0;
    }

    public function isApproved(): bool
    {
        return $this->status === 1;
    }

    // リレーション
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function correctionRestTimes()
    {
        return $this->hasMany(CorrectionRestTime::class);
    }
}
