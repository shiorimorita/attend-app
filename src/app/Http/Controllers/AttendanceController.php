<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceController extends Controller
{
    public function myAttendances()
    {
        /* ユーザーが選択した月を取得し表示・もしユーザーが月を選択しなければ今月を表示 */
        $month = request('month') ? Carbon::createFromFormat('Y-m', request('month'))->startOfMonth() : now()->startOfMonth();
        /* $prevMonth = 先月 */
        $prevMonth = $month->copy()->subMonth()->format('Y-m');
        /* $nextMonth = 今月 */
        $nextMonth = $month->copy()->addMonth()->format('Y-m');
        /* whereBetween('date', [A, B]) date が月初から月末まで入っている対象ユーザーの勤怠を取得 */
        $attendances = Attendance::with('breaks')->where('user_id', Auth::id())->whereBetween('date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])->get()->keyBy(fn($a) => $a->date);
        /* $period = 月のカレンダー*/
        $period = CarbonPeriod::create($month, $month->copy()->endOfMonth());
        /* $days = 月のカレンダー+対象ユーザーの勤怠情報付き */
        $days = collect($period)->map(function ($day) use ($attendances) {
            /* $days =6/1,6/2,6/3 つまりはコレクション？ これを mapすることによって   ['date' => 6/1, 'attendance' => null],  ['date' => 6/3, 'attendance' => Attendance],
といった連想配列にしている！！*/
            return [
                'date' => $day,
                'attendance' => $attendances->get($day->toDateString()),
            ];
        });

        return view('attendance-index', compact(
            'days',
            'month',
            'prevMonth',
            'nextMonth'
        ));
    }

    public function index()
    {
        $attendance = Attendance::where('user_id', Auth::id())->whereDate('date', today())->first();

        $isOnBreak = false;
        if ($attendance) {
            $isOnBreak = $attendance->breaks()->whereNull('break_out')->exists();
        }

        return view('attendance-status', compact('attendance', 'isOnBreak'));
    }

    public function clockIn()
    {
        Attendance::create([
            'user_id' => Auth::id(),
            'date' => today(),
            'clock_in' => now(),
        ]);

        return redirect('/attendance');
    }

    public function clockOut()
    {
        Attendance::where('user_id', Auth::id())->whereDate('date', today())->update(['clock_out' => now()]);
        return redirect('/attendance');
    }
}
