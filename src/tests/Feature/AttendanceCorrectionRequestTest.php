<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrection;
use Carbon\CarbonPeriod;

class AttendanceCorrectionRequestTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    /* 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される */
    public function test_correction_request_time_error()
    {
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        /** @var \App\Models\User $user */
        $this->actingAs($user);
        $response = $this->get('/attendance/detail/' . $attendance->id);

        $response = $this->post('/attendance/detail/' . $attendance->id, [
            'attendance_id' => $attendance->id,
            'clock_in' => '18:00',
            'clock_out' => '09:00',
            'correction_reason' => 'Test correction reason',
        ]);

        $response->assertSessionHasErrors(['clock_out' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    /* 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される */
    public function test_correction_request_break_time_error()
    {
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $break = AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        /** @var \App\Models\User $user */
        $this->actingAs($user);
        $response = $this->get('/attendance/detail/' . $attendance->id);
        $response = $this->post('/attendance/detail/' . $attendance->id, [
            'attendance_id' => $attendance->id,
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                [
                    'break_in' => '19:00',
                    'break_out' => '20:00',
                ],
            ],
            'description' => 'Test correction reason',
        ]);

        $response->assertSessionHasErrors(['breaks.0.break_in' => '休憩時間が不適切な値です']);
    }

    /* 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される */
    public function test_correction_request_break_end_time_error()
    {
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $break = AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        /** @var \App\Models\User $user */
        $this->actingAs($user);
        $response = $this->get('/attendance/detail/' . $attendance->id);
        $response = $this->post('/attendance/detail/' . $attendance->id, [
            'attendance_id' => $attendance->id,
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                [
                    'break_in' => '12:00',
                    'break_out' => '19:00',
                ],
            ],
            'description' => 'Test correction reason',
        ]);

        $response->assertSessionHasErrors(['breaks.0.break_out' => '休憩時間もしくは退勤時間が不適切な値です']);
    }

    /* 備考欄が未入力の場合のエラーメッセージが表示される */
    public function test_correction_request_description_empty_error()
    {
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        /** @var \App\Models\User $user */
        $this->actingAs($user);
        $response = $this->get('/attendance/detail/' . $attendance->id);
        $response = $this->post('/attendance/detail/' . $attendance->id, [
            'attendance_id' => $attendance->id,
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'description' => '',
        ]);

        $response->assertSessionHasErrors(['description' => '備考を記入してください']);
    }

    /* 修正申請処理が実行される */
    public function test_correction_request_success()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'date' => '2025-12-15',
        ]);

        /** @var \App\Models\User $staff */
        $this->actingAs($staff);
        $response = $this->get('/attendance/detail/' . $attendance->id);
        $this->post('/attendance/detail/' . $attendance->id, [
            'attendance_id' => $attendance->id,
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'description' => 'Test correction reason',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        /** @var \App\Models\User $admin */
        $this->actingAs($admin);
        $response = $this->get('/stamp_correction_request/list');
        $response->assertSee($staff->name);
        $response->assertSee('2025/12/15');
        $response->assertSee('Test correction reason');
        $response->assertSee('2025/12/21');

        $response = $this->get('/stamp_correction_request/approve/' . AttendanceCorrection::first()->id);
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('Test correction reason');
    }

    /* 「承認待ち」にログインユーザーが行った申請が全て表示されていること */
    public function test_correction_request_list_displays_user_requests()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        /** @var \App\Models\User $staff */
        $this->actingAs($staff);

        $dates = CarbonPeriod::create('2025-12-15', '2025-12-17');

        foreach ($dates as $date) {
            $attendance = Attendance::factory()->create([
                'user_id' => $staff->id,
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'date' => $date->format('Y-m-d'),
            ]);

            $this->get('/attendance/detail/' . $attendance->id);
            $this->post('/attendance/detail/' . $attendance->id, [
                'attendance_id' => $attendance->id,
                'clock_in' => '10:00',
                'clock_out' => '19:00',
                'description' => 'Test correction reason for ' . $date->format('Y-m-d'),
            ]);
        }

        $response = $this->get('/stamp_correction_request/list');
        $response->assertSee('2025/12/15');
        $response->assertSee('2025/12/16');
        $response->assertSee('2025/12/17');
        $response->assertDontSee('2025/12/18');
    }

    /* 「承認済み」に管理者が承認した修正申請が全て表示されている */
    public function test_correction_request_list_displays_approved_requests()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        /** @var \App\Models\User $staff */
        $this->actingAs($staff);
        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'date' => '2025-12-15',
        ]);

        $this->get('/attendance/detail/' . $attendance->id);
        $this->post('/attendance/detail/' . $attendance->id, [
            'attendance_id' => $attendance->id,
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'description' => 'Test correction reason',
        ]);

        $correction = AttendanceCorrection::where('attendance_id', $attendance->id)->first();
        $correction->update([
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        $response = $this->get('/stamp_correction_request/list?status=approved');
        $response->assertSee('2025/12/15');
        $response->assertSee('承認済み');
    }

    /* 各申請の「詳細」を押下すると勤怠詳細画面に遷移する */
    public function test_correction_request_detail_link_navigates_to_attendance_detail()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        /** @var \App\Models\User $staff */
        $this->actingAs($staff);
        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'date' => '2025-12-15',
        ]);

        $this->post('/attendance/detail/' . $attendance->id, [
            'attendance_id' => $attendance->id,
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'description' => 'Test correction reason',
        ]);

        $response = $this->get('/stamp_correction_request/list');
        $response->assertSee('/attendance/detail/' . $attendance->id);
        $response = $this->get('/attendance/detail/' . $attendance->id)->assertStatus(200);
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('Test correction reason');
    }
}
