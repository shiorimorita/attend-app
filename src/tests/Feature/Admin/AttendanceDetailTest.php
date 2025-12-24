<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Models\AttendanceBreak;

class AttendanceDetailTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    /* 勤怠詳細画面に表示されるデータが選択したものになっている */
    public function test_admin_can_view_attendance_detail()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'date' => '2025-12-21',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        /** @var \App\Models\User $admin */
        $response = $this->actingAs($admin)->get('/admin/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);
        $response->assertSee($staff->name);
        $response->assertSee('2025年');
        $response->assertSee('12月21日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }

    /* 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される */
    public function test_admin_sees_error_message_when_clock_in_is_after_clock_out()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'date' => '2025-12-21',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        /** @var \App\Models\User $admin */
        $this->actingAs($admin);

        $response = $this->post('/admin/attendance/detail/' . $attendance->id, [
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'description' => '修正内容',
        ]);

        $response->assertSessionHasErrors(['clock_out' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    /* 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される */
    public function test_admin_sees_error_message_when_break_in_is_after_clock_out()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'date' => '2025-12-21',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $break = AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        /** @var \App\Models\User $admin */
        $this->actingAs($admin);
        $response = $this->post('/admin/attendance/detail/' . $attendance->id, [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'description' => '修正内容',
            'breaks' => [
                $break->id => [
                    'break_in' => '19:00',
                    'break_out' => '20:00',
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['breaks.' . $break->id . '.break_in' => '休憩時間が不適切な値です']);
    }

    /* 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される */
    public function test_admin_sees_error_message_when_break_out_is_after_clock_out()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'date' => '2025-12-21',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $break = AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        /** @var \App\Models\User $admin */
        $this->actingAs($admin);
        $response = $this->post('/admin/attendance/detail/' . $attendance->id, [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'description' => '修正内容',
            'breaks' => [
                $break->id => [
                    'break_in' => '12:30',
                    'break_out' => '19:00',
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['breaks.' . $break->id . '.break_out' => '休憩時間もしくは退勤時間が不適切な値です']);
    }

    /* 備考欄が未入力の場合のエラーメッセージが表示される */
    public function test_admin_sees_error_message_when_description_is_empty()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'date' => '2025-12-21',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        /** @var \App\Models\User $admin */
        $this->actingAs($admin);

        $response = $this->post('/admin/attendance/detail/' . $attendance->id, [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'description' => '',
        ]);

        $response->assertSessionHasErrors(['description' => '備考を記入してください']);
    }
}
