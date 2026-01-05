<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\BreakCorrection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Http\Controllers\Concerns\FormatsAttendanceTime;

class AttendanceCorrectionController extends Controller
{
    use FormatsAttendanceTime;

    public function store(AttendanceCorrectionRequest $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        DB::transaction(
            function () use ($request, $attendance) {

                $correction = AttendanceCorrection::create([
                    'attendance_id' => $attendance->id,
                    'user_id' => Auth::id(),
                    'clock_in' => $request->clock_in,
                    'clock_out' => $request->clock_out,
                    'description' => $request->description,
                ]);

                foreach ($request->input('breaks', []) as $breakId => $b) {

                    $in = $b['break_in'] ?? null;
                    $out = $b['break_out'] ?? null;

                    if (blank($in) && blank($out)) {
                        BreakCorrection::create([
                            'attendance_correction_id' => $correction->id,
                            'attendance_break_id' => $breakId,
                            'break_in' => null,
                            'break_out' => null,
                            'is_deleted' => true,
                        ]);
                        continue;
                    }

                    BreakCorrection::create([
                        'attendance_correction_id' => $correction->id,
                        'attendance_break_id' => $breakId,
                        'break_in' => $b['break_in'],
                        'break_out' => $b['break_out'],
                    ]);
                }

                if ($nb = $request->input('new_break')) {
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
        );

        return back();
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $status = $request->input('status', 'pending');

        if ($user->role === 'admin') {

            $corrections = AttendanceCorrection::with('breakCorrections')->where('status', $status)->get();
        } else {
            $corrections = AttendanceCorrection::with('breakCorrections')->where(['user_id' => Auth::id(), 'status' => $status])->get();
        }

        return view('application-index', compact('corrections', 'status'));
    }

    public function showApprove($attendance_correct_request_id)
    {
        $correction = AttendanceCorrection::with([
            'attendance.breaks',
            'user',
            'breakCorrections',
        ])->findOrFail($attendance_correct_request_id);

        $attendance = $correction->attendance;

        $clockIn = $this->pickHi($correction->clock_in, $correction->attendance->clock_in);
        $clockOut = $this->pickHi($correction->clock_out, $correction->attendance->clock_out);

        $breakCorrectionMap = $correction->breakCorrections->keyBy('attendance_break_id');

        $actionUrl = url('/stamp_correction_request/approve/' . $attendance_correct_request_id);

        $readonly = true;
        $mode = 'admin';

        $breaks = $attendance->breaks->map(function ($break) use ($breakCorrectionMap) {
            $bc = $breakCorrectionMap->get($break->id);

            if ($bc?->is_deleted) {
                return null;
            }

            return [
                'id'        => $break->id,
                'break_in'  => $this->pickHi($bc?->break_in,  $break->break_in),
                'break_out' => $this->pickHi($bc?->break_out, $break->break_out),
            ];
        })->filter()->values();

        $newBreak = ['break_in' => '', 'break_out' => ''];

        $newBreakCorrection = $correction->breakCorrections->where('attendance_break_id', null)->first();
        if ($newBreakCorrection) {
            $newBreak = [
                'break_in'  => $this->formatHi($newBreakCorrection->break_in),
                'break_out' => $this->formatHi($newBreakCorrection->break_out),
            ];
        }

        return view('attendance-check', compact('attendance', 'correction', 'readonly', 'mode', 'clockIn', 'clockOut', 'breaks', 'newBreak', 'actionUrl'));
    }

    public function approve(Request $request, $attendance_correct_request_id)
    {
        $correction = AttendanceCorrection::with(['attendance.breaks', 'breakCorrections'])->findOrFail($attendance_correct_request_id);

        DB::transaction(function () use ($correction, $request) {

            $correction->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
            ]);

            $attendance = $correction->attendance;

            $attendance->update([
                'clock_in' => $correction->clock_in,
                'clock_out' => $correction->clock_out,
                'description' => $correction->description,
            ]);

            foreach ($correction->breakCorrections as $bc) {

                if ($bc->is_deleted) {
                    if ($bc->attendance_break_id) {
                        $attendance->breaks()->where('id', $bc->attendance_break_id)->delete();
                    }
                    continue;
                }

                if ($bc->attendance_break_id) {
                    $attendance->breaks()->where('id', $bc->attendance_break_id)->update([
                        'break_in'  => $bc->break_in,
                        'break_out' => $bc->break_out,
                    ]);
                    continue;
                }

                $attendance->breaks()->create([
                    'break_in'  => $bc->break_in,
                    'break_out' => $bc->break_out,
                ]);
            }
        });

        return back();
    }
}
