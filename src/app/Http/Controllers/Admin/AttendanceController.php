<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\RestTime;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * 日次勤怠一覧画面（管理者）
     * GET /admin/attendance/list
     */
    public function list(Request $request)
    {
        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))
            : Carbon::today();

        $users = User::where('role', 0)->get();

        $attendances = $users->map(function ($user) use ($date) {
            $attendance = Attendance::with(['restTimes' => function ($query) {
                    $query->orderBy('rest_start');
                }])
                ->where('user_id', $user->id)
                ->where('work_date', $date->toDateString())
                ->first();

            if (!$attendance) {
                return [
                    'id'         => null,
                    'user_id'    => $user->id,
                    'date_raw'   => $date->toDateString(),
                    'name'       => $user->name,
                    'clock_in'   => null,
                    'clock_out'  => null,
                    'rest_time'  => null,
                    'total_time' => null,
                ];
            }

            return $this->formatRow($user->name, $attendance, $date);
        });

        return view('admin.attendance.list', [
            'attendances'          => $attendances,
            'currentDate'          => $date->isoFormat('YYYY年M月D日'),
            'currentDateFormatted' => $date->format('Y/m/d'),
            'prevDate'             => $date->copy()->subDay()->toDateString(),
            'nextDate'             => $date->copy()->addDay()->toDateString(),
        ]);
    }

    /**
     * 勤怠レコードを取得or作成してから詳細画面にリダイレクト
     * GET /admin/attendance/detail/{user_id}/{date}
     */
    public function findOrCreateAndRedirect(int $userId, string $date)
    {
        $attendance = Attendance::firstOrCreate(
            ['user_id' => $userId, 'work_date' => $date],
            ['status'  => 3]
        );

        return redirect()->route('admin.attendance.detail', $attendance->id);
    }

    /**
     * 勤怠詳細画面（管理者）
     * GET /admin/attendance/{id}
     */
    public function detail(int $id)
    {
        $attendance = Attendance::with([
                'restTimes' => function ($query) {
                    $query->orderBy('rest_start');
                },
                'user',
            ])
            ->findOrFail($id);

        // 最新の申請を取得（承認待ち・承認済み問わず）
        $latestRequest = StampCorrectionRequest::with(['correctionRestTimes' => function ($query) {
                $query->orderBy('rest_start');
            }])
            ->where('attendance_id', $attendance->id)
            ->latest('created_at')
            ->first();

        // 承認待ちかどうか
        $isPending = $latestRequest && $latestRequest->status === 0;

        return view('admin.attendance.detail', [
            'attendance'    => $attendance,
            'latestRequest' => $latestRequest,
            'isPending'     => $isPending,
        ]);
    }

    /**
     * 勤怠直接修正（管理者）
     * POST /admin/attendance/{id}
     */
    public function update(AttendanceCorrectionRequest $request, int $id)
    {
        $attendance = Attendance::findOrFail($id);

        DB::transaction(function () use ($request, $attendance) {

            // 出勤・退勤・備考を直接更新
            $attendance->update([
                'clock_in'  => $request->clock_in
                    ? Carbon::parse($attendance->work_date->format('Y-m-d') . ' ' . $request->clock_in)
                    : null,
                'clock_out' => $request->clock_out
                    ? Carbon::parse($attendance->work_date->format('Y-m-d') . ' ' . $request->clock_out)
                    : null,
                'remarks'   => $request->remarks,
            ]);

            // 既存の休憩を全削除して再登録
            $attendance->restTimes()->delete();

            if ($request->has('rest_times')) {
                foreach ($request->rest_times as $rest) {
                    $start = $rest['rest_start'] ?? null;
                    $end   = $rest['rest_end']   ?? null;

                    if (empty($start) && empty($end)) {
                        continue;
                    }

                    RestTime::create([
                        'attendance_id' => $attendance->id,
                        'rest_start'    => $start
                            ? Carbon::parse($attendance->work_date->format('Y-m-d') . ' ' . $start)
                            : null,
                        'rest_end'      => $end
                            ? Carbon::parse($attendance->work_date->format('Y-m-d') . ' ' . $end)
                            : null,
                    ]);
                }
            }
        });

        return redirect()->route('admin.attendance.staff', ['id' => $attendance->user_id]);
    }

    /**
     * スタッフ別月次勤怠一覧画面（管理者）
     * GET /admin/attendance/staff/{id}
     */
    public function staffList(Request $request, int $id)
    {
        $user = User::where('role', 0)->findOrFail($id);

        $month = $request->query('month')
            ? Carbon::parse($request->query('month'))->startOfMonth()
            : Carbon::now()->startOfMonth();

        $prevMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');

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

        $attendances = [];
        for ($day = 1; $day <= $month->daysInMonth; $day++) {
            $date          = $month->copy()->day($day);
            $attendance    = $attendanceMap->get($date->format('Y-m-d'));
            $attendances[] = $this->formatRow($user->name, $attendance, $date);
        }

        return view('admin.attendance.staff', [
            'user'         => $user,
            'currentMonth' => $month->format('Y/m'),
            'prevMonth'    => $prevMonth,
            'nextMonth'    => $nextMonth,
            'attendances'  => $attendances,
        ]);
    }

    /**
     * CSV出力（管理者）
     * GET /admin/attendance/staff/{id}/csv
     */
    public function exportCsv(Request $request, int $id)
    {
        $user = User::where('role', 0)->findOrFail($id);

        $month = $request->query('month')
            ? Carbon::parse($request->query('month'))->startOfMonth()
            : Carbon::now()->startOfMonth();

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

        $rows = [];
        for ($day = 1; $day <= $month->daysInMonth; $day++) {
            $date       = $month->copy()->day($day);
            $attendance = $attendanceMap->get($date->format('Y-m-d'));
            $rows[]     = $this->formatRow($user->name, $attendance, $date);
        }

        $filename = $user->name . '_' . $month->format('Y-m') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            // BOM（Excel文字化け対策）
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['date']       ?? '',
                    $row['clock_in']   ?? '',
                    $row['clock_out']  ?? '',
                    $row['rest_time']  ?? '',
                    $row['total_time'] ?? '',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * 1ユーザー分の勤怠データを表示用配列に整形する
     */
    private function formatRow(string $name, ?Attendance $attendance, ?Carbon $date = null): array
    {
        $dateLabel = $date ? $date->isoFormat('MM/DD(ddd)') : null;

        if (!$attendance) {
            return [
                'id'         => null,
                'name'       => $name,
                'date'       => $dateLabel,
                'clock_in'   => null,
                'clock_out'  => null,
                'rest_time'  => null,
                'total_time' => null,
            ];
        }

        // 休憩合計（分）
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
            'user_id'    => $attendance->user_id,
            'date_raw'   => $date ? $date->toDateString() : null,
            'name'       => $name,
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
        ];
    }
}