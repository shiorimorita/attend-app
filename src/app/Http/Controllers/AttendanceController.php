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
            ->first(); // null or Model

        $approvedCorrection = $attendance->attendanceCorrections
            ->where('status', 'approved')
            ->sortByDesc('created_at')
            ->first(); // null or Model

        $correction = $pendingCorrection ?? $approvedCorrection;

        return view('attendance-check', [
            'attendance' => $attendance,
            'correction' => $correction,
            'mode' => Auth::user()->isAdmin() ? 'admin' : 'user',
        ]);
    }

    public function todayAttendance(Request $request)
    {
        $date = $request->date ? Carbon::parse($request->date) : today();
        $attendances = Attendance::with('breaks')->whereDate('date', today())->get();
        return view('staff-index', compact('attendances', 'date'));
    }

    public function store(Request $request, $id)
    {
        $attendance = Attendance::with('breaks')->findOrFail($id);

        DB::transaction(function () use ($request, $attendance) {

            $attendance->update([
                'clock_in' => $request->clock_in,
                'clock_out' => $request->clock_out,
            ]);

            foreach ($request->input('breaks', []) as $breakId => $b) {

                $breakIn = !empty($b['break_in']) ? $b['break_in'] : null;
                $breakOut = !empty($b['break_out']) ? $b['break_out'] : null;

                if (is_null($breakIn) && is_null($breakOut)) {
                    AttendanceBreak::where('id', $breakId)
                        ->where('attendance_id', $attendance->id)
                        ->delete();
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

        return back();
    }
}
