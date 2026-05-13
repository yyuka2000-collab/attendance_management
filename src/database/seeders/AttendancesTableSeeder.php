<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;

class AttendancesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 一般ユーザーのみ対象
        $users = User::where('role', 0)->get();

        // 過去30日分のダミーデータを作成（土日は除く）
        foreach ($users as $user) {
            $date = Carbon::today()->subDays(30);

            while ($date->lte(Carbon::today())) {
                // 土日はスキップ
                if ($date->isWeekend()) {
                    $date->addDay();
                    continue;
                }

                // 当日はステータスを「勤務外」のまま作成しない（打刻画面で操作するため）
                if ($date->isToday()) {
                    $date->addDay();
                    continue;
                }

                $clockIn  = $date->copy()->setTime(rand(8, 9), rand(0, 59), 0);
                $clockOut = $clockIn->copy()->addHours(rand(8, 9))->addMinutes(rand(0, 59));

                Attendance::create([
                    'user_id'   => $user->id,
                    'status'    => 3, // 退勤済
                    'clock_in'  => $clockIn,
                    'clock_out' => $clockOut,
                    'work_date' => $date->toDateString(),
                ]);

                $date->addDay();
            }
        }
    }
}
