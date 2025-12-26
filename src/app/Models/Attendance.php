<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
    ];

    protected $casts = [
        'date'      => 'date',
        'clock_in'  => 'datetime:H:i:s',
        'clock_out' => 'datetime:H:i:s',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breaks()
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    public function workMinutes(): int
    {
        return max(0, $this->boundMinutes() - $this->breakMinutes());
    }

    public function formatMinutes(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return sprintf('%d:%02d', $h, $m);
    }

    public function boundMinutes(): int
    {
        if (!$this->clock_in || !$this->clock_out) {
            return 0;
        }

        $seconds = $this->clock_out->diffInSeconds($this->clock_in);

        return (int) ceil($seconds / 60);
    }

    public function breakMinutes(): int
    {
        return $this->breaks()
            ->whereNotNull('break_in')
            ->whereNotNull('break_out')
            ->get()
            ->sum(function ($b) {
                $in  = $b->break_in->copy();
                $out = $b->break_out->copy();

                if ($out->lt($in)) {
                    $out->addDay();
                }

                $seconds = $out->diffInSeconds($in);

                return (int) ceil($seconds / 60);
            });
    }

    public function breakTime(): string
    {
        return $this->formatMinutes($this->breakMinutes());
    }

    public function totalMinutes(): int
    {
        return $this->workMinutes();
    }

    public function totalTime(): string
    {
        $minutes = $this->totalMinutes();

        $hours = intdiv($minutes, 60);
        $mins  = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $mins);
    }

    public function attendanceCorrections()
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    public function latestCorrection()
    {
        return $this->hasOne(AttendanceCorrection::class)->latest();
    }

    public function isPendingCorrection(): bool
    {
        return $this->attendanceCorrections()->where('status', 'pending')->exists();
    }

    public function isApproved(): bool
    {
        return $this->attendanceCorrections()
            ->where('status', 'approved')
            ->exists();
    }
}
