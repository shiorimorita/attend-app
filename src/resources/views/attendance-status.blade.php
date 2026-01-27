@extends('layouts.common')
@section('css')
<link rel="stylesheet" href="{{asset('css/attendance-status.css')}}">
@endsection
@section('content')
<main class="attendance__status">
    <p class="attendance__status-text">
        @if(!$attendance)
        勤務外
        @elseif($attendance->clock_out)
        退勤済
        @elseif($isOnBreak)
        休憩中
        @else
        出勤中
        @endif
    </p>
    <p class="attendance__status-date">{{now()->format('Y年n月j日')}}({{ ['日','月','火','水','木','金','土'][now()->dayOfWeek] }})</p>
    <p class="attendance__status-time {{$attendance?->clock_out ? 'attendance__status-time--after-clockout' : ''}}" id="current-time">{{now()->format('H:i')}}
        @if($attendance?->clock_out)
    <p class="attendance__status-clock-out">お疲れ様でした。</p>
    @elseif($isOnBreak)
    <form action="/attendance/break-out" method="post" class="attendance__status-form">
        @csrf
        <input type="hidden" id="break-out-time" name="break_out_time">
        <button type="submit" id="break-out-btn" class="attendance__status-button attendance__status-change-button--break">休憩戻</button>
    </form>
    @elseif($attendance?->clock_in)
    <div class="attendance__status-change-buttons">
        <form action="/attendance/clock-out" method="post">
            @csrf
            <input type="hidden" id="check-out-time" name="check_out_time">
            <button type="submit" id="check-out-btn" class="attendance__status-button common-btn">退勤</button>
        </form>
        <form action="/attendance/break-in" method="post">
            @csrf
            <input type="hidden" id="break-in-time" name="break_in_time">
            <button type="submit" id="break-in-btn" class="attendance__status-button  attendance__status-change-button--break">休憩入</button>
        </form>
    </div>
    @else
    <form action="/attendance/clock-in" method="post" class="attendance__status-form">
        @csrf
        <input type="hidden" id="check-in-time" name="check_in_time">
        <button type="submit" id="check-in-btn" class="attendance__status-button common-btn">出勤</button>
    </form>
    @endif
</main>
@endsection
@section('js')
<script>
    document.addEventListener("DOMContentLoaded", () => {
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, "0");
            const minutes = String(now.getMinutes()).padStart(2, "0");

            const el = document.getElementById("current-time");
            if (el) el.textContent = `${hours}:${minutes}`;
        }

        updateClock();
        setInterval(updateClock, 1000);

        const setNow = (id) => {
            const input = document.getElementById(id);
            if (input) input.value = new Date().toISOString();
        };

        document.getElementById("check-in-btn")?.addEventListener("click", () => setNow("check-in-time"));
        document.getElementById("check-out-btn")?.addEventListener("click", () => setNow("check-out-time"));
        document.getElementById("break-in-btn")?.addEventListener("click", () => setNow("break-in-time"));
        document.getElementById("break-out-btn")?.addEventListener("click", () => setNow("break-out-time"));
    });
</script>
@endsection