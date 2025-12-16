<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\BreakCorrection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceCorrectionController extends Controller
{
    public function store(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        DB::transaction(function () use ($request, $attendance) {

            $correction = AttendanceCorrection::create([
                'attendance_id' => $attendance->id,
                'user_id' => Auth::id(),
                'clock_in' => $request->clock_in,
                'clock_out' => $request->clock_out,
                'description' => $request->description,
            ]);

            foreach ($request->input('breaks', []) as $breakId => $b) {
                if ($b['break_in'] || $b['break_out']) {
                    BreakCorrection::create([
                        'attendance_correction_id' => $correction->id,
                        'attendance_break_id' => $breakId,
                        'break_in' => $b['break_in'],
                        'break_out' => $b['break_out'],
                    ]);
                }
            }

            if ($nb = $request->input('new_break')) { {
                    if (
                        !empty($nb['break_in']) &&
                        !empty($nb['break_out'])
                    )
                        BreakCorrection::create([
                            'attendance_correction_id' => $correction->id,
                            'attendance_break_id' => null,
                            'break_in' => $nb['break_in'],
                            'break_out' => $nb['break_out'],
                        ]);
                }
            }
        });

        return back();
    }

    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');

        $corrections = AttendanceCorrection::with('breakCorrections')->where(['user_id' => Auth::id(), 'status' => $status])->get();

        return view('application-index', compact('corrections', 'status'));
    }
}
