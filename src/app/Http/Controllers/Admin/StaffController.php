<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function list()
    {
        $users = User::where('role', 0)->get();

        return view('admin.staff.list', compact('users'));
    }

    public function attendance(Request $request, $id)
    {
        $user = User::where('role', 0)->findOrFail($id);

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

        return view('admin.attendance.staff', [
            'user'         => $user,
            'currentMonth' => $month->format('Y/m'),
            'prevMonth'    => $prevMonth,
            'nextMonth'    => $nextMonth,
            'attendances'  => $attendances,
        ]);
    }

    /**
     * CSV出力（FN045）
     * GET /admin/attendance/staff/{id}/csv
     */
    public function exportCsv(Request $request, $id)
    {
        $user = User::where('role', 0)->findOrFail($id);

        // ?month=2026/04 形式（Y/m）または 2026-04 形式（Y-m）どちらも受け付ける
        $monthParam = $request->query('month');
        if ($monthParam) {
            $monthParam = str_replace('/', '-', $monthParam);
            $month = Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth();
        } else {
            $month = Carbon::now()->startOfMonth();
        }

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

        // 当月の全日付ぶんの行を生成
        $rows = [];
        for ($day = 1; $day <= $month->daysInMonth; $day++) {
            $date       = $month->copy()->day($day);
            $attendance = $attendanceMap->get($date->format('Y-m-d'));
            $rows[]     = $this->formatRow($date, $attendance);
        }

        // CSVファイル名: 氏名_YYYY年MM月.csv
        $fileName = $user->name . '_' . $month->format('Y年m月') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');

            // BOM（Excelで文字化けしないように）
            fwrite($handle, "\xEF\xBB\xBF");

            // ヘッダー行
            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);

            // データ行
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['date'],
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
     * 1日分の勤怠データを表示用配列に整形する
     * ※ AttendanceController::formatRow() と同一ロジック
     */
    private function formatRow(Carbon $date, ?Attendance $attendance, array $pendingAttendanceIds = []): array
    {
        $dateLabel = $date->isoFormat('MM/DD(ddd)');

        if (!$attendance) {
            return [
                'id'         => null,
                'date'       => $dateLabel,
                'date_raw'   => $date->format('Y-m-d'),
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