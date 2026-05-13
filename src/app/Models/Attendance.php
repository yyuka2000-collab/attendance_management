<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',   // 0: 勤務外, 1: 出勤中, 2: 休憩中, 3: 退勤済
        'clock_in',
        'clock_out',
        'remarks',
        'work_date',
    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in'  => 'datetime',
        'clock_out' => 'datetime',
    ];

    // =========================================================
    // リレーション
    // =========================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function restTimes(): HasMany
    {
        // rest_start 昇順で取得（一覧・詳細で表示順を保証）
        return $this->hasMany(RestTime::class)->orderBy('rest_start');
    }

    public function stampCorrectionRequests(): HasMany
    {
        return $this->hasMany(StampCorrectionRequest::class);
    }

    // =========================================================
    // スコープ（追記）
    // =========================================================

    /**
     * 承認待ちの修正申請が存在するか判定するスコープ
     * 使用例: $attendance->hasPendingRequest()
     */
    public function hasPendingRequest(): bool
    {
        return $this->stampCorrectionRequests()
            ->where('status', 'pending')
            ->exists();
    }
}