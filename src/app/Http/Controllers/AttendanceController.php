<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Http\Controllers\Concerns\FormatsAttendanceTime;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\AttendanceCorrectionRequest;

class AttendanceController extends Controller
{
    use FormatsAttendanceTime;

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
        $user = Auth::user();
        $isAdminDirectEdit = request()->is('admin/attendance/detail/*');

        $attendance = Attendance::with([
            'breaks',
            'attendanceCorrections' => function ($q) {
                $q->where('status', 'pending')->with('breakCorrections');
            },
        ])->findOrFail($id);

        $correction = $attendance->attendanceCorrections->first();

        $clockIn = $this->pickHi($correction?->clock_in, $attendance->clock_in);
        $clockOut = $this->pickHi($correction?->clock_out, $attendance->clock_out);

        $breakCorrectionMap = $correction
            ? $correction->breakCorrections->keyBy('attendance_break_id')
            : collect();

        $breaks = $attendance->breaks->map(function ($break) use ($breakCorrectionMap) {
            $bc = $breakCorrectionMap->get($break->id);

            if ($bc?->is_deleted) {
                return null;
            }

            return [
                'id' => $break->id,
                'break_in' => $this->pickHi($bc?->break_in, $break->break_in),
                'break_out' => $this->pickHi($bc?->break_out, $break->break_out),
            ];
        })->filter()->values();

        $newBreak = ['break_in'  => '', 'break_out' => '',];

        if ($correction) {
            $newBreakCorrection = $correction->breakCorrections->where('attendance_break_id', null)->first();
            if ($newBreakCorrection) {
                $newBreak = [
                    'break_in'  => $this->formatHi($newBreakCorrection->break_in),
                    'break_out' => $this->formatHi($newBreakCorrection->break_out),
                ];
            }
        }

        $readonly = $user->isAdmin() ? false : (bool) $correction;

        $actionUrl = null;
        if (! $readonly) {
            $actionUrl = $isAdminDirectEdit
                ? url('/admin/attendance/detail/' . $attendance->id)
                : route('attendance.correction.request', ['id' => $attendance->id]);
        }

        $mode = $user->isAdmin() ? 'admin' : 'user';

        return view('attendance-check', compact('attendance', 'correction', 'readonly', 'mode', 'clockIn', 'clockOut', 'breaks', 'newBreak', 'actionUrl'));
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
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'request_type' => 'admin_edit',
                'description' => $request->input('description'),
            ]);

            foreach ($attendance->breaks as $break) {
                $correction->breakCorrections()->create([
                    'attendance_break_id' => $break->id,
                    'break_in' => $break->break_in,
                    'break_out' => $break->break_out,
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
