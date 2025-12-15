<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use App\Models\AttendanceBreak;
use Illuminate\Support\Facades\Auth;
use PhpParser\Node\Stmt\Break_;

class AttendanceBreakController extends Controller
{
    public function breakIn()
    {
        $attendance = Attendance::where('user_id', Auth::id())->where('date', today())->first();

        if (!$attendance) {
            return back()->withErrors('本日の出勤はありません');
        }

        $attendance->breaks()->create([
            'break_in' => now()->format('H:i:s'),
        ]);

        $isOnBreak = $attendance->breaks()->whereNull('break_out')->exists();

        if ($isOnBreak) {
            return back()->withErrors('すでに休憩中です');
        }

        return back();
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

        return back();
    }
}
