<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionRestTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'stamp_correction_request_id',
        'rest_start',
        'rest_end',
    ];

    protected $casts = [
        'rest_start' => 'datetime',
        'rest_end'   => 'datetime',
    ];

    // リレーション
    public function stampCorrectionRequest()
    {
        return $this->belongsTo(StampCorrectionRequest::class);
    }
}
