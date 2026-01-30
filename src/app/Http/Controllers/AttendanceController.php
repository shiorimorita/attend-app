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
use App\Models\AttendanceBreak;

class AttendanceController extends Controller
{
    use FormatsAttendanceTime;

    public function buildMonthlyAttendances(int $userId, ?string $monthParam): array
    {
        $month = $monthParam ? Carbon::parse($monthParam . '-01') : now()->startOfMonth();

        $prevMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');
        $monthStart = $month->copy();
        $monthEnd = $month->copy()->endOfMonth();

        $attendances = Attendance::with('breaks')->where('user_id', $userId)
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get()->keyBy(fn($a) => $a->date->toDateString());

        $period = CarbonPeriod::create($monthStart, $monthEnd);

        $days = collect($period)->map(fn($day) => [
            'date' => $day,
            'attendance' => $attendances->get($day->toDateString()),
        ]);

        return [
            'days' => $days,
            'month' => $month,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
        ];
    }

    public function myAttendances()
    {
        $user = Auth::user();
        $data = $this->buildMonthlyAttendances($user->id, request('month'));

        return view('attendance-index', array_merge($data, ['user' => $user]));
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

    public function create($userId, $date)
    {
        $user = User::findOrFail($userId);
        $date = Carbon::parse($date);
        $routeName = request()->is('admin/*') ? 'admin.attendance.store' : 'attendance.store';
        $actionUrl = route($routeName, ['userId' => $user->id, 'date' => $date->format('Y-m-d')]);

        $attendance = null;
        $correction = null;
        $readonly = false;

        return view('attendance-check', compact('user', 'attendance', 'correction', 'readonly', 'date', 'actionUrl'));
    }

    public function store(AttendanceCorrectionRequest $request, $userId, $date)
    {
        $attendanceDate = Carbon::parse($date)->toDateString();

        DB::transaction(function () use ($request, $userId, $attendanceDate) {

            $attendance = Attendance::create([
                'user_id' => $userId,
                'date' => $attendanceDate,
                'description' => $request->input('description'),
                'clock_in' => $request->input('clock_in'),
                'clock_out' => $request->input('clock_out'),
            ]);

            if ($request->filled('new_break.break_in') && $request->filled('new_break.break_out')) {
                AttendanceBreak::create([
                    'attendance_id' => $attendance->id,
                    'break_in' => $request->input('new_break.break_in'),
                    'break_out' => $request->input('new_break.break_out'),
                ]);
            }
        });

        if (request()->is('admin/*')) {
            return redirect('/admin/attendance/list');
        }

        return redirect()->route('attendance.list');
    }

    public function edit($id)
    {
        $user = Auth::user();
        $isAdminDirectEdit = request()->is('admin/attendance/detail/*');

        $attendance = Attendance::with([
            'breaks',
            'attendanceCorrections' => function ($q) {
                $q->where('status', 'pending')->latest()->with('breakCorrections');
            },
        ])->findOrFail($id);

        $correction = $attendance->attendanceCorrections->first();

        $isApproveView = $user->isAdmin() && $correction && $correction->status === 'pending';

        $descriptionDefault =
            ($correction?->status === 'pending' ? $correction->description : null)
            ?? $attendance->description
            ?? '';

        $clockIn  = $this->pickHi($correction?->clock_in,  $attendance->clock_in);
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
                'id'        => $break->id,
                'break_in'  => $this->pickHi($bc?->break_in,  $break->break_in),
                'break_out' => $this->pickHi($bc?->break_out, $break->break_out),
            ];
        })->filter()->values();

        $newBreak = ['break_in' => '', 'break_out' => ''];

        if ($correction) {
            $newBreakCorrection = $correction->breakCorrections
                ->where('attendance_break_id', null)
                ->first();

            if ($newBreakCorrection) {
                $newBreak = [
                    'break_in'  => $this->formatHi($newBreakCorrection->break_in),
                    'break_out' => $this->formatHi($newBreakCorrection->break_out),
                ];
            }
        }

        if ($isApproveView) {
            $readonly = true;
            $actionUrl = url('/admin/attendance/detail/' . $attendance->id);
        } else {
            // 管理者が勤怠詳細画面にアクセスした場合でも、pending 状態なら readonly にする
            if ($user->isAdmin() && $isAdminDirectEdit) {
                $readonly = $correction && $correction->status === 'pending';
            } else {
                $readonly = $user->isAdmin() ? false : ($correction && $correction->status === 'pending');
            }

            $actionUrl = null;
            if (! $readonly) {
                $actionUrl = $isAdminDirectEdit
                    ? url('/admin/attendance/detail/' . $attendance->id)
                    : route('attendance.correction.request', ['id' => $attendance->id]);
            }
        }

        $mode = $user->isAdmin() ? 'admin' : 'user';

        return view('attendance-check', compact(
            'attendance',
            'correction',
            'readonly',
            'mode',
            'clockIn',
            'clockOut',
            'breaks',
            'newBreak',
            'actionUrl',
            'descriptionDefault'
        ));
    }

    public function todayAttendance(Request $request)
    {
        $date = $request->date ? Carbon::parse($request->date) : today();
        $attendances = Attendance::with('breaks')->whereDate('date', today())->get();
        return view('staff-index', compact('attendances', 'date'));
    }

    public function update(AttendanceCorrectionRequest $request, $id)
    {
        $attendance = Attendance::with([
            'breaks',
            'attendanceCorrections' => function ($q) {
                $q->where('status', 'pending')->with('breakCorrections');
            },
        ])->findOrFail($id);

        $pendingCorrection = $attendance->attendanceCorrections->first();

        // pendingの補正リクエストがある場合 → 承認処理
        if ($pendingCorrection) {
            DB::transaction(function () use ($pendingCorrection) {

                $pendingCorrection->update([
                    'status' => 'approved',
                    'approved_by' => Auth::id(),
                ]);

                $attendance = $pendingCorrection->attendance;

                $attendance->update([
                    'clock_in' => $pendingCorrection->clock_in,
                    'clock_out' => $pendingCorrection->clock_out,
                    'description' => $pendingCorrection->description,
                ]);

                foreach ($pendingCorrection->breakCorrections as $bc) {

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
        } else {
            // pendingの補正リクエストがない場合 → 管理者による直接更新
            // (初回編集、またはapproved後の再編集)
            DB::transaction(function () use ($request, $attendance) {

                // 変更前の状態をapprovedの補正レコードとして保存
                $correction = $attendance->attendanceCorrections()->create([
                    'user_id' => $attendance->user_id,
                    'clock_in' => $attendance->clock_in,
                    'clock_out' => $attendance->clock_out,
                    'status' => 'approved',
                    'approved_by' => Auth::id(),
                    'request_type' => 'admin_edit',
                    'description' => $attendance->description,
                ]);

                foreach ($attendance->breaks as $break) {
                    $correction->breakCorrections()->create([
                        'attendance_break_id' => $break->id,
                        'break_in' => $break->break_in,
                        'break_out' => $break->break_out,
                    ]);
                }

                // 勤怠データを新しい値で更新
                $attendance->update([
                    'clock_in' => $request->clock_in,
                    'clock_out' => $request->clock_out,
                    'description' => $request->description,
                ]);

                // 既存の休憩時間を更新または削除
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

                // 新しい休憩時間を追加
                if ($nb = $request->input('new_break')) {
                    if (!empty($nb['break_in']) && !empty($nb['break_out'])) {
                        $attendance->breaks()->create([
                            'break_in'  => $nb['break_in'],
                            'break_out' => $nb['break_out'],
                        ]);
                    }
                }
            });
        }

        return redirect('admin/attendance/list');
    }

    public function exportCsv(Request $request)
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $monthParam = $request->input('month');
        $userId = $request->input('user_id');

        abort_if(!$monthParam || !$userId, 400);

        $user = User::findOrFail($userId);

        $data = $this->buildMonthlyAttendances($user->id, $monthParam);
        $days = $data['days'];
        $month = $data['month'];

        $fileName = sprintf('%s_%s_attendance.csv', $user->name, $month->format('Y_m'));

        return response()->streamDownload(function () use ($days, $user) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['日付', '出勤', '退勤', '休憩', '合計']);
            foreach ($days as $day) {
                $attendance = $day['attendance'];

                fputcsv($out, [
                    $day['date']->format('Y-m-d'),
                    $attendance?->clock_in?->format('H:i') ?? '',
                    $attendance?->clock_out?->format('H:i') ?? '',
                    ($attendance && $attendance->breakTime() !== '0:00')
                        ? $attendance->breakTime()
                        : '',
                    $attendance?->totalTime() ?? '',
                ]);
            }

            fclose($out);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
