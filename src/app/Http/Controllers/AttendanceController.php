<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
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
