<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;

class StaffAttendanceListTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;
    /* 自分が行った勤怠情報が全て表示されている */
    public function test_user_attendance_list_shows_own_attendances_only()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $dates = ['2025-12-20', '2025-12-21', '2025-12-22'];
        $attendances = [];

        foreach ($dates as $date) {
            $attendance = Attendance::factory()->create([
                'user_id' => $user->id,
                'date' => $date,
            ]);

            AttendanceBreak::factory()->create([
                'attendance_id' => $attendance->id,
            ]);

            $attendances[] = $attendance;
        }

        $response = $this->actingAs($user)->get('/attendance/list?month=2025-12');

        foreach ($attendances as $attendance) {
            $response->assertSee($attendance->date->format('m/d'));
            $response->assertSee($attendance->clock_in->format('H:i'));
            $response->assertSee($attendance->clock_out->format('H:i'));
        }
    }

    /* 勤怠一覧画面に遷移した際に現在の月が表示される */
    public function test_attendance_list_displays_current_month_on_navigation()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertSee('2025/12');
    }

    /* 「前月」を押下した時に表示月の前月の情報が表示される */
    public function test_attendance_list_previous_month_navigation()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=2025-11');
        $response->assertSee('2025/11');
    }

    /* 「翌月」を押下した時に表示月の翌月の情報が表示される */
    public function test_attendance_list_next_month_navigation()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=2026-01');
        $response->assertSee('2026/01');
    }

    /* 「詳細」を押下すると、その日の勤怠詳細画面に遷移する */
    public function test_attendance_list_detail_navigation()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id)->assertStatus(200);
        $response->assertSee(Carbon::now()->format('Y年'));
        $response->assertSee(Carbon::now()->format('m月d日'));
    }
}
