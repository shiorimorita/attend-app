<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminStaffListTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    /* 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる */
    public function test_admin_can_view_all_staff_list()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $staffList = User::factory()->count(3)->create([
            'role' => 'staff',
        ]);

        /** @var \App\Models\User $admin */
        $response = $this->actingAs($admin)->get('/admin/staff/list');
        $response->assertStatus(200);
        foreach ($staffList as $staff) {
            $response->assertSee($staff->name);
            $response->assertSee($staff->email);
        }
    }

    /* ユーザーの勤怠情報が正しく表示される */
    public function test_admin_staff_list_shows_no_attendance_info()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        $attendances = Attendance::factory()
            ->count(3)
            ->sequence(
                ['date' => '2025-12-05'],
                ['date' => '2025-12-10'],
                ['date' => '2025-12-15'],
            )
            ->create([
                'user_id' => $staff->id,
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
            ]);

        /** @var \App\Models\User $admin */
        $response = $this->actingAs($admin)->get('/admin/attendance/staff/' . $staff->id);

        $response->assertSeeInOrder([
            '12/05',
            '09:00',
            '18:00',
            '12/10',
            '09:00',
            '18:00',
            '12/15',
            '09:00',
            '18:00',
        ]);
    }

    /* 「前日」を押下した時に表示月の前日の情報が表示される */
    public function test_admin_can_view_previous_day_attendance()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);

        Attendance::factory()->create([
            'user_id' => $staff->id,
            'date' => '2025-12-20',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        /** @var \App\Models\User $admin */
        $day21 = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2025-12-21')
            ->assertStatus(200);

        $day21->assertSee('date=2025-12-20');

        $day20 = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2025-12-20')
            ->assertStatus(200);

        $day20->assertSee('09:00');
        $day20->assertSee('18:00');
    }

    /* 「翌日」を押下した時に表示月の前日の情報が表示される */
    public function test_admin_can_view_next_day_attendance()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);

        Attendance::factory()->create([
            'user_id' => $staff->id,
            'date' => '2025-12-22',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        /** @var \App\Models\User $admin */
        $day21 = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2025-12-21')
            ->assertStatus(200);

        $day21->assertSee('date=2025-12-22');

        $day22 = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2025-12-22')
            ->assertStatus(200);

        // 翌日の勤怠が表示されること（表示形式はUIに合わせて）
        $day22->assertSee('09:00');
        $day22->assertSee('18:00');
    }

    /* 「詳細」を押下すると、その日の勤怠詳細画面に遷移する */
    public function test_admin_can_navigate_to_attendance_detail()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'date' => '2025-12-21',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        /** @var \App\Models\User $admin */
        $response = $this->actingAs($admin)
            ->get(route('admin.attendance.list', ['date' => '2025-12-21']))
            ->assertStatus(200);

        $response->assertSee(
            route('admin.attendance.detail', $attendance->id),
            false
        );

        $detailResponse = $this->actingAs($admin)
            ->get(route('admin.attendance.detail', $attendance->id))
            ->assertStatus(200);

        $detailResponse->assertSee('09:00');
        $detailResponse->assertSee('18:00');
    }
}
