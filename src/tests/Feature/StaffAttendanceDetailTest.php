<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Models\AttendanceBreak;

class StaffAttendanceDetailTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    /* 勤怠詳細画面の「名前」がログインユーザーの氏名になっている */
    public function test_attendance_detail_name_displayed_correctly()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(
            ['role' => 'staff'],
        );

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
        ]);

        $this->actingAs($user);
        $response = $this->get('/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);
        $response->assertSee($user->name);
    }

    /* 勤怠詳細画面の「日付」が選択した日付になっている */
    public function test_attendance_detail_date_displayed_correctly()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(
            ['role' => 'staff'],
        );

        $attendanceDate = Carbon::create(2025, 12, 25);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $attendanceDate->format('Y-m-d'),
        ]);

        $this->actingAs($user);
        $response = $this->get('/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);
        $response->assertSee($attendanceDate->format('Y年'));
        $response->assertSee($attendanceDate->format('m月d日'));
    }

    /* 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している */
    public function test_attendance_detail_stamp_times_displayed_correctly()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(
            ['role' => 'staff'],
        );

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);
        $response = $this->get('/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);
        $response->assertSee($attendance->clock_in->format('H:i'));
        $response->assertSee($attendance->clock_out->format('H:i'));
    }

    /* 「休憩」にて記されている時間がログインユーザーの打刻と一致している */
    public function test_attendance_detail_break_times_displayed_correctly()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(
            ['role' => 'staff'],
        );

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $break = AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
        ]);

        $this->actingAs($user);
        $response = $this->get('/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);
        $response->assertSee($break->break_in->format('H:i'));
        $response->assertSee($break->break_out->format('H:i'));
    }
}
