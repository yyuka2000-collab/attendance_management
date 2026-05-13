<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\RestTime;

class RestTimesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 全勤怠レコードに対して休憩データを作成
        $attendances = Attendance::all();

        foreach ($attendances as $attendance) {
            // 休憩回数をランダムで1〜2回に設定
            $restCount = rand(1, 2);
            $clockIn   = Carbon::parse($attendance->clock_in);
            $clockOut  = Carbon::parse($attendance->clock_out);

            // 1回目の休憩（昼休み想定：出勤から3〜4時間後）
            $restStart1 = $clockIn->copy()->addHours(rand(3, 4))->addMinutes(rand(0, 30));
            $restEnd1   = $restStart1->copy()->addMinutes(rand(45, 60));

            // restEnd が退勤時刻を超えないよう保護
            if ($restEnd1->gte($clockOut)) {
                $restEnd1 = $clockOut->copy()->subMinutes(30);
            }

            RestTime::create([
                'attendance_id' => $attendance->id,
                'rest_start'    => $restStart1,
                'rest_end'      => $restEnd1,
            ]);

            // 2回目の休憩（午後の休憩想定：1回目終了から2〜3時間後）
            if ($restCount === 2) {
                $restStart2 = $restEnd1->copy()->addHours(rand(2, 3))->addMinutes(rand(0, 30));
                $restEnd2   = $restStart2->copy()->addMinutes(rand(10, 20));

                // restEnd が退勤時刻を超えないよう保護
                if ($restEnd2->gte($clockOut)) {
                    continue;
                }

                RestTime::create([
                    'attendance_id' => $attendance->id,
                    'rest_start'    => $restStart2,
                    'rest_end'      => $restEnd2,
                ]);
            }
        }
    }
}
