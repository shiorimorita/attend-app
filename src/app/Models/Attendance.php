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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breaks()
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    private function toDateTime(string $date, ?string $time): ?Carbon
    {
        if (!$date || !$time) return null;

        // "H:i" なら "H:i:s" に寄せる
        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            $time .= ':00';
        }

        // date が "Y-m-d" 前提
        return Carbon::createFromFormat('Y-m-d H:i:s', "{$date} {$time}");
    }

    public function boundMinutes(): int
    {
        $in  = $this->toDateTime($this->date, $this->clock_in);
        $out = $this->toDateTime($this->date, $this->clock_out);
        if (!$in || !$out) return 0;

        if ($out->lt($in)) $out->addDay();

        return $in->diffInMinutes($out);
    }

    public function breakMinutes(): int
    {
        return $this->breaks->sum(function ($b) {
            if (!$b->break_in || !$b->break_out) return 0;

            $in = $this->toDateTime($this->date, $b->break_in);
            $out = $this->toDateTime($this->date, $b->break_out);
            if (!$in || !$out) return 0;

            if ($out->lt($in)) {
                $out->addDay();
            }
            return $in->diffInMinutes($out);
        });
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

    public function breakTime(): string
    {
        return $this->formatMinutes($this->breakMinutes());
    }

    public function totalTime(): string
    {
        return $this->formatMinutes($this->workMinutes());
    }
}
