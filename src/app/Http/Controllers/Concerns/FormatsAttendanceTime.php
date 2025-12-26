<?php

namespace App\Http\Controllers\Concerns;

use Carbon\Carbon;

trait FormatsAttendanceTime
{
    /**
     *
     *
     *
     * @param  mixed  $value  Carbon|DateTime|string|null
     * @return string
     */
    protected function formatHi($value)
    {
        if (!$value) {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i');
        }

        return Carbon::parse($value)->format('H:i');
    }

    /**
     *
     *
     *
     * @param  mixed  $primary
     * @param  mixed  $fallback
     * @return string
     */
    protected function pickHi($primary, $fallback): string
    {
        return $this->formatHi($primary) ?: $this->formatHi($fallback);
    }
}
