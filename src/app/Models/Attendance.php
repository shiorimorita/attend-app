<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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

        $in  = $this->clock_in->copy();
        $out = $this->clock_out->copy();

        // 日跨ぎ対応
        if ($out->lt($in)) {
            $out->addDay();
        }

        return $in->diffInMinutes($out);
    }

    public function breakMinutes(): int
    {
        return $this->breaks->sum(function ($b) {
            if (!$b->break_in || !$b->break_out) {
                return 0;
            }

            $in  = $b->break_in->copy();
            $out = $b->break_out->copy();

            if ($out->lt($in)) {
                $out->addDay();
            }

            return $in->diffInMinutes($out);
        });
    }

    public function breakTime(): string
    {
        return $this->formatMinutes($this->breakMinutes());
    }

    public function totalTime(): string
    {
        return $this->formatMinutes($this->workMinutes());
    }

    public function attendanceCorrections()
    {
        return $this->hasMany(AttendanceCorrection::class);
    }
}
