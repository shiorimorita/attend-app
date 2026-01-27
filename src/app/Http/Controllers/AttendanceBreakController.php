<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;

class AttendanceBreakController extends Controller
{
    public function breakIn()
    {
        $attendance = Attendance::where('user_id', Auth::id())->where('date', today())->first();

        if (!$attendance) {
            return back()->withErrors('本日の出勤はありません');
        }

        $isOnBreak = $attendance->breaks()->whereNull('break_out')->exists();

        if ($isOnBreak) {
            return back()->withErrors('すでに休憩中です');
        }

        $attendance->breaks()->create([
            'break_in' => now()->format('H:i:s'),
        ]);

        return redirect('/attendance');
    }

    public function breakOut()
    {
        $attendance = Attendance::where('user_id', Auth::id())->where('date', today())->first();

        if (!$attendance) {
            return back()->withErrors('本日の出勤はありません');
        }

        $updated = $attendance->breaks()->whereNull('break_out')->update(['break_out' => now()->format('H:i:s')]);

        if ($updated === 0) {
            return back()->withErrors('休憩中ではありません');
        }

        return redirect('/attendance');
    }
}
