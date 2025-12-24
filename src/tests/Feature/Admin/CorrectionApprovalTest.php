<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Carbon\Carbon;

class CorrectionApprovalTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    /* 承認待ちの修正申請が全て表示されている */
    public function test_admin_can_view_all_correction_requests()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 0, 0, 0));

        $admin = User::factory()->create(['role' => 'admin']);

        $staffList = User::factory()->count(2)->create(['role' => 'staff'])
            ->each(function ($staff) {
                $attendance = Attendance::factory()->create([
                    'user_id' => $staff->id,
                    'date' => '2025-12-10',
                ]);
                AttendanceCorrection::factory()->create([
                    'attendance_id' => $attendance->id,
                    'user_id' => $staff->id,
                    'description' => '交通遅延のため',
                ]);
            });

        /** @var \App\Models\User $admin */
        $response = $this->actingAs($admin)->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('交通遅延のため');
        $response->assertSee('2025/12/10');
        $response->assertSee('2025/12/21');
        foreach ($staffList as $staff) {
            $response->assertSee($staff->name);
        }
    }

    /* 承認済みの修正申請が全て表示されている */
    public function test_admin_can_view_all_approved_correction_requests()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 0, 0, 0));

        $admin = User::factory()->create(['role' => 'admin']);

        $staffList = User::factory()->count(2)->create(['role' => 'staff'])
            ->each(function ($staff) {
                $attendance = Attendance::factory()->create([
                    'user_id' => $staff->id,
                    'date' => '2025-12-10',
                ]);
                AttendanceCorrection::factory()->create([
                    'attendance_id' => $attendance->id,
                    'user_id' => $staff->id,
                    'status' => 'approved',
                    'description' => '交通遅延のため',
                ]);
            });

        /** @var \App\Models\User $admin */
        $response = $this->actingAs($admin)->get('/stamp_correction_request/list?status=approved');
        $response->assertStatus(200);
        $response->assertSee('2025/12/10');
        $response->assertSee('2025/12/21');
        $response->assertSee('交通遅延のため');

        foreach ($staffList as $staff) {
            $response->assertSee($staff->name);
        }
    }

    /* 修正申請の詳細内容が正しく表示されている */
    public function test_admin_can_view_correction_request_details()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 0, 0, 0));

        $admin = User::factory()->create(['role' => 'admin']);

        $staff = User::factory()->create(['role' => 'staff']);
        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'date' => '2025-12-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        $correction = AttendanceCorrection::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $staff->id,
            'status' => 'pending',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
            'description' => '交通遅延のため',
        ]);

        /** @var \App\Models\User $admin */
        $response = $this->actingAs($admin)->get('/stamp_correction_request/approve/' . $correction->id);
        $response->assertStatus(200);
        $response->assertSee($staff->name);
        $response->assertSee('2025年');
        $response->assertSee('12月10日');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('交通遅延のため');
    }

    /* 修正申請の承認処理が正しく行われる */
    public function test_admin_can_approve_correction_request()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 0, 0, 0));

        $admin = User::factory()->create(['role' => 'admin']);

        $staff = User::factory()->create(['role' => 'staff']);
        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'date' => '2025-12-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        $correction = AttendanceCorrection::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $staff->id,
            'status' => 'pending',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
            'description' => '交通遅延のため',
        ]);

        /** @var \App\Models\User $admin */
        $this->actingAs($admin)->post('/stamp_correction_request/approve/' . $correction->id, [
            'status' => 'approve',
            'approved_by' => $admin->id,
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'description' => '交通遅延のため',
        ]);

        $this->assertDatabaseHas('attendance_corrections', [
            'id' => $correction->id,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);
    }
}
