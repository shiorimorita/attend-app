<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakCorrection extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'attendance_correction_id',
        'attendance_break_id',
        'break_in',
        'break_out',
    ];

    public function attendanceBreak()
    {
        return $this->belongsTo(AttendanceBreak::class);
    }

    public function attendanceCorrection()
    {
        return $this->belongsTo(AttendanceCorrection::class);
    }
}
