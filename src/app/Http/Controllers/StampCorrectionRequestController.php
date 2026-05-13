<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\StampCorrectionRequest;

class StampCorrectionRequestController extends Controller
{
    /**
     * 申請一覧画面（承認待ち・承認済み）
     * GET /stamp_correction_request/list  (PG06)
     */
    public function list()
    {
        $user = Auth::user();

        $pending = StampCorrectionRequest::with('attendance')
            ->where('user_id', $user->id)
            ->where('status', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        $approved = StampCorrectionRequest::with('attendance')
            ->where('user_id', $user->id)
            ->where('status', 1)
            ->orderBy('approved_at', 'desc')
            ->get();

        return view('stamp_correction_request.list', compact('pending', 'approved'));
    }
}