<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\AttendanceCorrectionRequest;
use Illuminate\Support\Carbon as SupportCarbon;

class AttendanceController extends Controller
{
    public function buildMonthlyAttendances(int $userId, ?string $monthParam): array
    {
        $month = $monthParam ? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth() : now()->startOfMonth();

        $attendances = Attendance::with('breaks')->where('user_id', $userId)
            ->whereBetween('date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->get()->keyBy(fn($a) => $a->date->toDateString());

        $period = CarbonPeriod::create($month, $month->copy()->endOfMonth());

        $days = collect($period)->map(fn($day) => [
            'date' => $day,
            'attendance' => $attendances->get($day->toDateString()),
        ]);

        return [
            'days' => $days,
            'month' => $month,
            'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
        ];
    }

    public function myAttendances()
    {
        $data = $this->buildMonthlyAttendances(Auth::id(), request('month'));

        return view('attendance-index', $data);
    }

    public function staff($id)
    {
        $user = User::findOrFail($id);

        $data = $this->buildMonthlyAttendances($user->id, request('month'));

        return view('attendance-index', array_merge(
            $data,
            ['user' => $user]
        ));
    }

    public function dailyAttendance()
    {
        $date = request('date') ? Carbon::createFromFormat('Y-m-d', request('date')) : now();

        $prevDate = $date->copy()->subDay()->format('Y-m-d');
        $nextDate = $date->copy()->addDay()->format('Y-m-d');

        $users = User::where('role', 'staff')->with([
            'attendances' => function ($q) use ($date) {
                $q->whereDate('date', $date->toDateString())->with('breaks');
            }
        ])->get()
            ->map(function ($user) {
                $user->dailyAttendance = $user->attendances->first();
                return $user;
            });

        return view('attendance-daily-index', compact('date', 'prevDate', 'nextDate', 'users'));
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

    public function edit($id)
    {
        $attendance = Attendance::with([
            'breaks',
            'attendanceCorrections.breakCorrections',
        ])->findOrFail($id);

        $pendingCorrection = $attendance->attendanceCorrections
            ->where('status', 'pending')
            ->sortByDesc('created_at')
            ->first();

        $correction = $pendingCorrection;

        $user = Auth::user();
        $isAdminDirectEdit = request()->is('admin/attendance/detail/*');

        $clockIn  = $correction && $correction->clock_in
            ? (new \Carbon\Carbon($correction->clock_in))->format('H:i')
            : ($attendance->clock_in ? $attendance->clock_in->format('H:i') : '');
        $clockOut = $correction && $correction->clock_out
            ? (new \Carbon\Carbon($correction->clock_out))->format('H:i')
            : ($attendance->clock_out ? $attendance->clock_out->format('H:i') : '');


        $breakCorrectionMap = $correction
            ? $correction->breakCorrections->keyBy('attendance_break_id')
            : collect();

        $breaks = $attendance->breaks->map(function ($break) use ($breakCorrectionMap) {
            $bc = $breakCorrectionMap->get($break->id);

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
        })->filter();

        $newBreak = [
            'break_in'  => '',
            'break_out' => '',
        ];

        if ($correction) {
            $newBreakCorrection = $correction->breakCorrections->where('attendance_break_id', null)->first();
            if ($newBreakCorrection) {
                $newBreak = [
                    'break_in'  => $newBreakCorrection->break_in ? (new \Carbon\Carbon($newBreakCorrection->break_in))->format('H:i') : '',
                    'break_out' => $newBreakCorrection->break_out ? (new \Carbon\Carbon($newBreakCorrection->break_out))->format('H:i') : '',
                ];
            }
        }

        $readonly = $user->isAdmin()
            ? false
            : (bool) $pendingCorrection;

        $actionUrl = null;
        if (! $readonly) {
            $actionUrl = $isAdminDirectEdit
                ? url('/admin/attendance/detail/' . $attendance->id)
                : route('attendance.correction.request', ['id' => $attendance->id]);
        }

        return view('attendance-check', [
            'attendance' => $attendance,
            'correction' => $correction,
            'readonly' => $readonly,
            'mode' => Auth::user()->isAdmin() ? 'admin' : 'user',
            'clockIn' => $clockIn,
            'clockOut' => $clockOut,
            'breaks' => $breaks,
            'newBreak' => $newBreak,
            'actionUrl' => $actionUrl,
        ]);
    }

    public function todayAttendance(Request $request)
    {
        $date = $request->date ? Carbon::parse($request->date) : today();
        $attendances = Attendance::with('breaks')->whereDate('date', today())->get();
        return view('staff-index', compact('attendances', 'date'));
    }

    public function store(AttendanceCorrectionRequest $request, $id)
    {
        $attendance = Attendance::with('breaks')->findOrFail($id);

        DB::transaction(function () use ($request, $attendance) {

            $correction = $attendance->attendanceCorrections()->create([
                'user_id' => $attendance->user_id,
                'clock_in' => $attendance->clock_in,
                'clock_out' => $attendance->clock_out,
                'status' => 'approved', // 管理者による直接編集
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'request_type' => 'admin_edit',
                'description' => $request->input('description'),
            ]);

            foreach ($attendance->breaks as $break) {
                $correction->breakCorrections()->create([
                    'attendance_break_id' => $break->id,
                    'break_in' => $break->break_in,
                    'break_out' => $break->break_out,
                    'is_deleted' => false,
                ]);
            }

            $attendance->update([
                'clock_in' => $request->clock_in,
                'clock_out' => $request->clock_out,
            ]);

            foreach ($request->input('breaks', []) as $breakId => $b) {

                $breakIn = $b['break_in'] ?? null;
                $breakOut = $b['break_out'] ?? null;

                if (blank($breakIn) && blank($breakOut)) {
                    $attendance->breaks()->where('id', $breakId)->delete();
                    continue;
                }

                $attendance->breaks()->where('id', $breakId)->update([
                    'break_in' => $breakIn,
                    'break_out' => $breakOut,
                ]);
            }

            if ($nb = $request->input('new_break')) {
                if (!empty($nb['break_in']) && !empty($nb['break_out'])) {
                    $attendance->breaks()->create([
                        'break_in'  => $nb['break_in'],
                        'break_out' => $nb['break_out'],
                    ]);
                }
            }
        });

        return redirect('admin/attendance/list');
    }
}
