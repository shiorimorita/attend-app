<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    /* その日になされた全ユーザーの勤怠情報が正確に確認できる */
    public function test_admin_can_view_all_users_attendance_list_for_the_day()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $staff1 = User::factory()->create([
            'role' => 'staff',
        ]);

        $staff2 = User::factory()->create([
            'role' => 'staff',
        ]);

        Attendance::factory()->create([
            'user_id' => $staff1->id,
            'date' => '2025-12-21',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $staff2->id,
            'date' => '2025-12-21',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);

        /** @var \App\Models\User $admin */
        $response = $this->actingAs($admin)->get('/admin/attendance/list');
        $response->assertStatus(200);
        $response->assertSee('2025/12/21');
        $response->assertSee($staff1->name);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee($staff2->name);
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }

    /* 遷移した際に現在の日付が表示される */
    public function test_admin_attendance_list_displays_current_date_on_navigation()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        /** @var \App\Models\User $admin */
        $response = $this->actingAs($admin)->get('/admin/attendance/list');
        $response->assertStatus(200);
        $response->assertSee('2025年');
        $response->assertSee('12月21日');
    }

    /* 「前日」を押下した時に前の日の勤怠情報が表示される */
    public function test_admin_can_view_previous_day_attendance_list()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        Attendance::factory()->create([
            'user_id' => $staff->id,
            'date' => '2025-12-20',
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
        ]);

        /** @var \App\Models\User $admin */
        $this->actingAs($admin);
        $response = $this->get('/admin/attendance/list');
        $response->assertSee('2025-12-20');
        $response = $this->get('/admin/attendance/list?date=2025-12-20');
        $response->assertSee('2025/12/20');
        $response->assertStatus(200);
        $response->assertSee($staff->name);
        $response->assertSee('08:30');
        $response->assertSee('17:30');
    }

    /* 「翌日」を押下した時に次の日の勤怠情報が表示される */
    public function test_admin_can_view_next_day_attendance_list()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        Attendance::factory()->create([
            'user_id' => $staff->id,
            'date' => '2025-12-22',
            'clock_in' => '10:30:00',
            'clock_out' => '19:30:00',
        ]);

        /** @var \App\Models\User $admin */
        $this->actingAs($admin);
        $response = $this->get('/admin/attendance/list');
        $response->assertSee('2025-12-22');
        $response = $this->get('/admin/attendance/list?date=2025-12-22');
        $response->assertSee('2025/12/22');
        $response->assertStatus(200);
        $response->assertSee($staff->name);
        $response->assertSee('10:30');
        $response->assertSee('19:30');
    }
}
