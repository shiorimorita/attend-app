<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\BreakCorrection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\AttendanceCorrectionRequest;

class AttendanceCorrectionController extends Controller
{
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
                        'is_deleted' => false,
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
                            'is_deleted' => false,
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

        $clockIn  = $correction->clock_in ? (new \Carbon\Carbon($correction->clock_in))->format('H:i') : ($correction->attendance->clock_in ? $correction->attendance->clock_in->format('H:i') : '');
        $clockOut = $correction->clock_out ? (new \Carbon\Carbon($correction->clock_out))->format('H:i') : ($correction->attendance->clock_out ? $correction->attendance->clock_out->format('H:i') : '');
        $newBreak = [
            'break_in'  => '',
            'break_out' => '',
        ];

        $attendance = $correction->attendance;


        $actionUrl = url('/stamp_correction_request/approve/' . $attendance_correct_request_id);

        $readonly = true;

        $breakCorrectionMap = $correction->breakCorrections->keyBy('attendance_break_id');

        $breaks = $attendance->breaks->map(
            function ($break) use ($breakCorrectionMap) {
                $bc = $breakCorrectionMap->get($break->id);

                // 削除フラグが立っている場合はスキップ
                if ($bc && $bc->is_deleted) {
                    return null;
                }

                $breakIn = ($bc && $bc->break_in)
                    ? (new \Carbon\Carbon($bc->break_in))->format('H:i')
                    : ($break->break_in ? $break->break_in->format('H:i') : '');

                $breakOut = ($bc && $bc->break_out)
                    ? (new \Carbon\Carbon($bc->break_out))->format('H:i')
                    : ($break->break_out ? $break->break_out->format('H:i') : '');

                return [
                    'id' => $break->id,
                    'break_in' => $breakIn,
                    'break_out' => $breakOut,
                ];
            }
        )->filter();

        // 新規追加の休憩時間を取得
        $newBreakCorrection = $correction->breakCorrections->where('attendance_break_id', null)->first();
        if ($newBreakCorrection) {
            $newBreak = [
                'break_in'  => $newBreakCorrection->break_in ? (new \Carbon\Carbon($newBreakCorrection->break_in))->format('H:i') : '',
                'break_out' => $newBreakCorrection->break_out ? (new \Carbon\Carbon($newBreakCorrection->break_out))->format('H:i') : '',
            ];
        }

        return view('attendance-check', [
            'attendance' => $correction->attendance,
            'correction' => $correction,
            'readonly' => $readonly,
            'mode' => 'admin',
            'clockIn' => $clockIn,
            'clockOut' => $clockOut,
            'newBreak' => $newBreak,
            'actionUrl' => $actionUrl,
            'breaks' => $breaks,
        ]);
    }

    public function approve(Request $request, $attendance_correct_request_id)
    {
        $correction = AttendanceCorrection::with(['attendance.breaks', 'breakCorrections'])->findOrFail($attendance_correct_request_id);

        DB::transaction(function () use ($correction, $request) {

            $correction->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'description' => $request->input('description'),
                'clock_in' => $request->input('clock_in'),
                'clock_out' => $request->input('clock_out'),
            ]);

            $attendance = $correction->attendance;

            $attendance->update([
                'clock_in' => $request->input('clock_in'),
                'clock_out' => $request->input('clock_out'),
            ]);

            $submittedBreakIds = [];

            foreach ($request->input('breaks', []) as $breakId => $b) {

                $breakIn = $b['break_in'] ?? null;
                $breakOut = $b['break_out'] ?? null;

                if (blank($breakIn) && blank($breakOut)) {
                    continue;
                }

                $submittedBreakIds[] = $breakId;

                $attendance->breaks()->where('id', $breakId)->update([
                    'break_in'  => $breakIn,
                    'break_out' => $breakOut,
                ]);
            }

            $attendance->breaks()->whereNotIn('id', $submittedBreakIds)->delete();

            if ($nb = $request->input('new_break')) {
                if (!empty($nb['break_in']) && !empty($nb['break_out'])) {
                    $attendance->breaks()->create([
                        'break_in'  => $nb['break_in'],
                        'break_out' => $nb['break_out'],
                    ]);
                }
            }
        });

        return back();
    }
}
