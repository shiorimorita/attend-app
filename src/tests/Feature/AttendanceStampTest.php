<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceStampTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */

    /* 現在の日時情報がUIと同じ形式で出力されている */
    public function test_current_datetime_is_displayed_in_ui_format()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee($now->format('Y年n月j日'));
        $response->assertSee('(' . ['日', '月', '火', '水', '木', '金', '土'][$now->dayOfWeek] . ')');
        $response->assertSee($now->format('H:i'));
    }

    /* 勤務外の場合、勤怠ステータスが正しく表示される */
    public function test_attendance_status_out_of_working_hours()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 10, 0, 0));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('勤務外');
    }

    /* 出勤中の場合、勤怠ステータスが正しく表示される */
    public function test_attendance_status_working()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $this->actingAs($user)->post('/attendance/clock-in', [
            'clock_in' => Carbon::now()->format('H:i'),
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('出勤中');
    }

    /* 休憩中の場合、勤怠ステータスが正しく表示される */
    public function test_attendance_status_on_break()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $this->actingAs($user)->post('/attendance/clock-in');

        // 休憩開始
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 12, 0, 0));
        $this->actingAs($user)->post('/attendance/break-in');

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩中');
    }

    /* 退勤済の場合、勤怠ステータスが正しく表示される */
    public function test_attendance_status_clocked_out()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $this->actingAs($user)->post('/attendance/clock-in');

        Carbon::setTestNow(Carbon::create(2025, 12, 21, 18, 0, 0));
        $this->actingAs($user)->post('/attendance/clock-out');

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('退勤済');
    }

    /* 出勤ボタンが正しく機能する */
    public function test_clock_in_button_functionality()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('出勤');

        $response = $this->actingAs($user)->post('/attendance/clock-in');
        $response->assertRedirect('/attendance');
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('出勤中');
    }

    /* 出勤は一日一回のみできる */
    public function test_clock_in_only_once_per_day()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $this->actingAs($user)->post('/attendance/clock-in');

        Carbon::setTestNow(Carbon::create(2025, 12, 21, 18, 0, 0));
        $this->actingAs($user)->post('/attendance/clock-out');

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertDontSee('id="check-in-btn"', false);
    }

    /* 出勤時刻が勤怠一覧画面で確認できる */
    public function test_clock_in_time_displayed_in_attendance_list()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $this->actingAs($user)->post('/attendance/clock-in');

        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertSee('09:00');
    }

    /* 休憩ボタンが正しく機能する */
    public function test_break_in_button_functionality()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $this->actingAs($user)->post('/attendance/clock-in');

        Carbon::setTestNow(Carbon::create(2025, 12, 21, 12, 0, 0));
        $response = $this->actingAs($user)->post('/attendance/break-in');
        $response->assertRedirect('/attendance');

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩中');
    }

    /* 休憩は一日に何回でもできる */
    public function test_break_in_multiple_times_per_day()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'staff',
        ]);
        $this->actingAs($user)->post('/attendance/clock-in');

        Carbon::setTestNow(Carbon::create(2025, 12, 21, 12, 0, 0));
        $this->actingAs($user)->post('/attendance/break-in');
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 12, 30, 0));
        $this->actingAs($user)->post('/attendance/break-out');

        Carbon::setTestNow(Carbon::create(2025, 12, 21, 14, 0, 0));
        $this->actingAs($user)->post('/attendance/break-in');
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 14, 30, 0));
        $this->actingAs($user)->post('/attendance/break-out');

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩入');
        $this->assertDatabaseCount('attendance_breaks', 2);
    }

    /* 休憩戻ボタンが正しく機能する */
    public function test_break_out_button_functionality()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $this->actingAs($user)->post('/attendance/clock-in');

        Carbon::setTestNow(Carbon::create(2025, 12, 21, 12, 0, 0));
        $this->actingAs($user)->post('/attendance/break-in');
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩戻');

        Carbon::setTestNow(Carbon::create(2025, 12, 21, 12, 30, 0));
        $response = $this->actingAs($user)->post('/attendance/break-out');
        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendance_breaks', [
            'break_out' => '12:30:00',
        ]);

        $this->assertDatabaseMissing('attendance_breaks', [
            'break_out' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('出勤中');
    }

    /* 休憩戻は一日に何回でもできる */
    public function test_break_out_multiple_times_per_day()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'staff',
        ]);
        $this->actingAs($user)->post('/attendance/clock-in');

        Carbon::setTestNow(Carbon::create(2025, 12, 21, 12, 0, 0));
        $this->actingAs($user)->post('/attendance/break-in');
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 12, 30, 0));
        $this->actingAs($user)->post('/attendance/break-out');

        Carbon::setTestNow(Carbon::create(2025, 12, 21, 14, 0, 0));
        $this->actingAs($user)->post('/attendance/break-in');
        $response = $this->get('/attendance');
        $response->assertSee('休憩戻');
        $response->assertDontSee('休憩入');
    }

    /* 休憩時刻が勤怠一覧画面で確認できる */
    public function test_break_times_displayed_in_attendance_list()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $this->actingAs($user)->post('/attendance/clock-in');

        Carbon::setTestNow(Carbon::create(2025, 12, 21, 12, 0, 0));
        $this->actingAs($user)->post('/attendance/break-in');

        Carbon::setTestNow(Carbon::create(2025, 12, 21, 12, 30, 0));
        $this->actingAs($user)->post('/attendance/break-out');

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertSeeInOrder([
            '12/21',
            '0:30',
        ]);
    }

    /* 退勤ボタンが正しく機能する */
    public function test_clock_out_button_functionality()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $this->actingAs($user)->post('/attendance/clock-in');
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('出勤中');

        Carbon::setTestNow(Carbon::create(2025, 12, 21, 18, 0, 0));
        $response = $this->actingAs($user)->post('/attendance/clock-out');
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('退勤');
    }

    /* 退勤時刻が勤怠一覧画面で確認できる */
    public function test_clock_out_time_displayed_in_attendance_list()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 9, 0, 0));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('勤務外');

        $this->actingAs($user)->post('/attendance/clock-in');
        Carbon::setTestNow(Carbon::create(2025, 12, 21, 18, 0, 0));
        $this->actingAs($user)->post('/attendance/clock-out');

        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertSee('18:00');
        $response->assertSeeInOrder([
            '12/21',
            '09:00',
            '18:00',
        ]);
    }
}
