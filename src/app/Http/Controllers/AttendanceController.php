<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\AttendanceCorrectionRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use App\Models\RestTime;
use App\Models\StampCorrectionRequest;
use App\Models\CorrectionRestTime;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * 勤怠打刻画面の表示
     * GET /attendance
     */
    public function index()
    {
        $user  = Auth::user();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        // 0: 勤務外, 1: 出勤中, 2: 休憩中, 3: 退勤済
        $statusMap = [
            0 => 'off',
            1 => 'working',
            2 => 'break',
            3 => 'done',
        ];

        $status = $attendance ? ($statusMap[$attendance->status] ?? 'off') : 'off';

        return view('attendance.index', [
            'status'      => $status,
            'currentDate' => Carbon::now()->isoFormat('YYYY年M月D日(ddd)'),
            'currentTime' => Carbon::now()->format('H:i'),
        ]);
    }

    /**
     * 出勤処理
     * POST /attendance/start
     */
    public function start()
    {
        $user  = Auth::user();
        $today = Carbon::today();

        $exists = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->exists();

        if (!$exists) {
            Attendance::create([
                'user_id'   => $user->id,
                'work_date' => $today,
                'status'    => 1,
                'clock_in'  => Carbon::now(),
            ]);
        }

        return redirect('/attendance');
    }

    /**
     * 退勤処理
     * POST /attendance/end
     */
    public function end()
    {
        $user  = Auth::user();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if ($attendance && $attendance->status === 1) {
            $attendance->update([
                'status'    => 3,
                'clock_out' => Carbon::now(),
            ]);
        }

        return redirect('/attendance');
    }

    /**
     * 休憩入処理
     * POST /attendance/break-start
     */
    public function breakStart()
    {
        $user  = Auth::user();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if ($attendance && $attendance->status === 1) {
            RestTime::create([
                'attendance_id' => $attendance->id,
                'rest_start'    => Carbon::now(),
            ]);

            $attendance->update(['status' => 2]);
        }

        return redirect('/attendance');
    }

    /**
     * 休憩戻処理
     * POST /attendance/break-end
     */
    public function breakEnd()
    {
        $user  = Auth::user();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if ($attendance && $attendance->status === 2) {
            $restTime = RestTime::where('attendance_id', $attendance->id)
                ->whereNull('rest_end')
                ->latest('rest_start')
                ->first();

            if ($restTime) {
                $restTime->update(['rest_end' => Carbon::now()]);
            }

            $attendance->update(['status' => 1]);
        }

        return redirect('/attendance');
    }

    /**
     * 勤怠一覧画面の表示（FN023, FN024, FN025）
     * GET /attendance/list
     */
    public function list(Request $request)
    {
        $user = Auth::user();

        // ?month=2023-06 形式。未指定なら当月
        $month = $request->query('month')
            ? Carbon::parse($request->query('month'))->startOfMonth()
            : Carbon::now()->startOfMonth();

        $prevMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');

        // 当月の勤怠レコードを一括取得
        $attendanceMap = Attendance::with(['restTimes' => function ($query) {
            $query->orderBy('rest_start');
        }])
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth(),
            ])
            ->get()
            ->keyBy(fn($a) => $a->work_date->format('Y-m-d'));

        // 承認待ち申請がある attendance_id を配列で取得
        $pendingAttendanceIds = StampCorrectionRequest::where('user_id', $user->id)
            ->where('status', 0)
            ->pluck('attendance_id')
            ->toArray();

        // 当月の全日付ぶんの行を生成
        $attendances = [];
        for ($day = 1; $day <= $month->daysInMonth; $day++) {
            $date          = $month->copy()->day($day);
            $attendance    = $attendanceMap->get($date->format('Y-m-d'));
            $attendances[] = $this->formatRow($date, $attendance, $pendingAttendanceIds);
        }

        return view('attendance.list', [
            'currentMonth' => $month->format('Y/m'),
            'prevMonth'    => $prevMonth,
            'nextMonth'    => $nextMonth,
            'attendances'  => $attendances,
        ]);
    }

    /**
     * 勤怠詳細画面の表示（FN026）
     * GET /attendance/detail/{id}
     */
    public function detail(int $id)
    {
        $user       = Auth::user();
        $attendance = Attendance::with(['restTimes' => function ($query) {
            $query->orderBy('rest_start');
        }])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        // 最新の申請を取得（承認待ち・承認済み問わず）
        $latestRequest = StampCorrectionRequest::with(['correctionRestTimes' => function ($query) {
            $query->orderBy('rest_start');
        }])
            ->where('attendance_id', $attendance->id)
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->first();

        // 承認待ち申請が存在するか確認（FN027）
        $isPending = $latestRequest && $latestRequest->status === 0;

        return view('attendance.detail', [
            'attendance'    => $attendance,
            'latestRequest' => $latestRequest,
            'isPending'     => $isPending,
        ]);
    }

    /**
     * 修正申請処理（FN028, FN029, FN030）
     * POST /attendance/detail/{id}
     */
    public function correct(AttendanceCorrectionRequest $request, int $id)
    {
        $user       = Auth::user();
        $attendance = Attendance::where('user_id', $user->id)->findOrFail($id);

        // 承認待ちの申請がある場合は申請不可
        $isPending = StampCorrectionRequest::where('attendance_id', $attendance->id)
            ->where('status', 0)
            ->exists();

        if ($isPending) {
            return redirect()->back()->withErrors(['pending' => '承認待ちのため修正はできません。']);
        }

        DB::transaction(function () use ($request, $attendance, $user) {

            // 申請レコード作成
            $correctionRequest = StampCorrectionRequest::create([
                'attendance_id' => $attendance->id,
                'user_id'       => $user->id,
                'new_clock_in'  => $request->clock_in
                    ? Carbon::parse($attendance->work_date->format('Y-m-d') . ' ' . $request->clock_in)
                    : null,
                'new_clock_out' => $request->clock_out
                    ? Carbon::parse($attendance->work_date->format('Y-m-d') . ' ' . $request->clock_out)
                    : null,
                'remarks'       => $request->remarks,
                'status'        => 0,
            ]);

            // 休憩の修正申請レコード作成（空欄のみの行は除外）
            if ($request->has('rest_times')) {
                foreach ($request->rest_times as $rest) {
                    $start = $rest['rest_start'] ?? null;
                    $end   = $rest['rest_end']   ?? null;

                    if (empty($start) && empty($end)) {
                        continue;
                    }

                    CorrectionRestTime::create([
                        'stamp_correction_request_id' => $correctionRequest->id,
                        'rest_start' => $start
                            ? Carbon::parse($attendance->work_date->format('Y-m-d') . ' ' . $start)
                            : null,
                        'rest_end'   => $end
                            ? Carbon::parse($attendance->work_date->format('Y-m-d') . ' ' . $end)
                            : null,
                    ]);
                }
            }
        });

        return redirect('/stamp_correction_request/list');
    }

    /**
     * 1日分の勤怠データを表示用配列に整形する
     */
    private function formatRow(Carbon $date, ?Attendance $attendance, array $pendingAttendanceIds = []): array
    {
        $dateLabel = $date->isoFormat('MM/DD(ddd)');

        if (!$attendance) {
            return [
                'id'         => null,
                'date'       => $dateLabel,
                'clock_in'   => null,
                'clock_out'  => null,
                'rest_time'  => null,
                'total_time' => null,
                'is_pending' => false,
            ];
        }

        // 休憩合計（分）: rest_end が null のレコードは除外
        $restMinutes = 0;
        foreach ($attendance->restTimes as $rt) {
            if ($rt->rest_start && $rt->rest_end) {
                $start = Carbon::parse($rt->rest_start)->second(0);
                $end   = Carbon::parse($rt->rest_end)->second(0);
                $restMinutes += $start->diffInMinutes($end);
            }
        }

        // 勤務合計（分）= 退勤 - 出勤 - 休憩合計
        $totalMinutes = null;
        if ($attendance->clock_in && $attendance->clock_out) {
            $clockIn      = Carbon::parse($attendance->clock_in)->second(0);
            $clockOut     = Carbon::parse($attendance->clock_out)->second(0);
            $worked       = $clockIn->diffInMinutes($clockOut);
            $totalMinutes = $worked - $restMinutes;
        }

        return [
            'id'         => $attendance->id,
            'date'       => $dateLabel,
            'clock_in'   => $attendance->clock_in
                ? Carbon::parse($attendance->clock_in)->format('H:i')
                : null,
            'clock_out'  => $attendance->clock_out
                ? Carbon::parse($attendance->clock_out)->format('H:i')
                : null,
            'rest_time'  => $restMinutes > 0
                ? sprintf('%d:%02d', intdiv($restMinutes, 60), $restMinutes % 60)
                : null,
            'total_time' => $totalMinutes !== null
                ? sprintf('%d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60)
                : null,
            'is_pending' => in_array($attendance->id, $pendingAttendanceIds),
        ];
    }
}