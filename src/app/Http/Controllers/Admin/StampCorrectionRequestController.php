<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\StampCorrectionRequest;
use App\Models\Attendance;
use App\Models\RestTime;
use Carbon\Carbon;

class StampCorrectionRequestController extends Controller
{
    /**
     * 修正申請一覧画面（承認待ち・承認済み）
     * GET /stamp_correction_request/list  (PG12)
     */
    public function list()
    {
        $pending = StampCorrectionRequest::with(['user', 'attendance'])
            ->where('status', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        $approved = StampCorrectionRequest::with(['user', 'attendance'])
            ->where('status', 1)
            ->orderBy('approved_at', 'desc')
            ->get();

        return view('admin.stamp_correction_request.list', [
            'pending'  => $pending,
            'approved' => $approved,
        ]);
    }

    /**
     * 修正申請承認画面
     * GET /admin/stamp_correction_request/approve/{attendance_correct_request_id}  (PG13)
     */
    public function show(int $attendance_correct_request_id)
    {
        $correctionRequest = StampCorrectionRequest::with([
                'user',
                'attendance',
                'correctionRestTimes',
            ])
            ->findOrFail($attendance_correct_request_id);

        $isPending = $correctionRequest->isPending();

        return view('admin.stamp_correction_request.detail', [
            'correctionRequest' => $correctionRequest,
            'isPending'         => $isPending,
        ]);
    }

    /**
     * 修正申請の承認処理（FN051）
     * POST /admin/stamp_correction_request/approve/{attendance_correct_request_id}
     */
    public function approve(int $attendance_correct_request_id)
    {
        $correctionRequest = StampCorrectionRequest::with([
                'attendance',
                'correctionRestTimes',
            ])
            ->findOrFail($attendance_correct_request_id);

        // すでに承認済みの場合はスキップ
        if ($correctionRequest->isApproved()) {
            return redirect()->route('stamp_correction_request.list');
        }

        DB::transaction(function () use ($correctionRequest) {

            $attendance = $correctionRequest->attendance;

            // 勤怠レコードを申請内容で上書き（FN051-2）
            $attendance->update([
                'clock_in'  => $correctionRequest->new_clock_in  ?? $attendance->clock_in,
                'clock_out' => $correctionRequest->new_clock_out ?? $attendance->clock_out,
                'remarks'   => $correctionRequest->remarks,
            ]);

            // 休憩レコードを差し替え（既存を削除して申請分で再作成）
            if ($correctionRequest->correctionRestTimes->isNotEmpty()) {
                RestTime::where('attendance_id', $attendance->id)->delete();

                foreach ($correctionRequest->correctionRestTimes as $crt) {
                    RestTime::create([
                        'attendance_id' => $attendance->id,
                        'rest_start'    => $crt->rest_start,
                        'rest_end'      => $crt->rest_end,
                    ]);
                }
            }

            // 申請ステータスを承認済みに更新（FN051-1, FN051-3）
            $correctionRequest->update([
                'status'      => 1,
                'approved_at' => Carbon::now(),
                'approved_by' => auth('admin')->id(),
            ]);
        });

        // 承認後は同じ詳細画面にリダイレクト（承認済みボタンを表示するため）
        return redirect()->route('admin.stamp_correction_request.show', [
            'attendance_correct_request_id' => $attendance_correct_request_id,
        ]);
    }
}