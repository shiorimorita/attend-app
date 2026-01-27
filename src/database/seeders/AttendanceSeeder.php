<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // スタッフユーザーのIDを取得
        $staffUser = DB::table('users')
            ->where('email', 'staff@example.com')
            ->first();

        if (!$staffUser) {
            return;
        }

        // 過去30日分のダミーデータを作成
        $endDate = Carbon::today();
        $startDate = $endDate->copy()->subDays(29);

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            // 土日はスキップ（オプション：必要に応じて削除）
            if ($date->isWeekend()) {
                continue;
            }

            // 出勤時刻：09:00
            $clockIn = $date->copy()->setTime(9, 0, 0);

            // 退勤時刻：18:00～19:00の間でランダム
            $clockOut = $date->copy()->setTime(18, rand(0, 60), rand(0, 59));

            // 勤怠レコードを挿入
            $attendanceId = DB::table('attendances')->insertGetId([
                'user_id' => $staffUser->id,
                'date' => $date->format('Y-m-d'),
                'clock_in' => $clockIn->format('Y-m-d H:i:s'),
                'clock_out' => $clockOut->format('Y-m-d H:i:s'),
                'description' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 休憩データを作成
            $breaks = [];

            // 必須休憩：12:00～13:00
            $breaks[] = [
                'attendance_id' => $attendanceId,
                'break_in' => $date->copy()->setTime(12, 0, 0)->format('Y-m-d H:i:s'),
                'break_out' => $date->copy()->setTime(13, 0, 0)->format('Y-m-d H:i:s'),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // ランダムで追加休憩を入れる（50%の確率）
            if (rand(0, 1) === 1) {
                // 09:00～12:00または13:00～18:00の間でランダムな休憩
                $isBeforeLunch = rand(0, 1) === 1;

                if ($isBeforeLunch) {
                    // 午前の休憩：09:30～11:30の間で開始
                    $breakStartHour = 9;
                    $breakStartMinute = rand(30, 59);
                    if ($breakStartMinute > 30) {
                        $breakStartHour = 10;
                        $breakStartMinute = rand(0, 59);
                    }
                } else {
                    // 午後の休憩：13:30～16:30の間で開始
                    $breakStartHour = rand(13, 16);
                    $breakStartMinute = rand(0, 59);
                    if ($breakStartHour === 13) {
                        $breakStartMinute = rand(30, 59);
                    }
                }

                $additionalBreakIn = $date->copy()->setTime($breakStartHour, $breakStartMinute, 0);
                $additionalBreakOut = $additionalBreakIn->copy()->addMinutes(rand(10, 30));

                $breaks[] = [
                    'attendance_id' => $attendanceId,
                    'break_in' => $additionalBreakIn->format('Y-m-d H:i:s'),
                    'break_out' => $additionalBreakOut->format('Y-m-d H:i:s'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // 休憩データを挿入
            DB::table('attendance_breaks')->insert($breaks);
        }
    }
}
