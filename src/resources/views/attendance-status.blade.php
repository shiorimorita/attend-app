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
    <p class="attendance__status-time">{{now()->format('H:i')}}</p>
    @if($attendance?->clock_out)
    <p class="attendance__status-clock-out">お疲れ様でした。</p>
    @elseif($isOnBreak)
    <form action="/attendance/break-out" method="post" class="attendance__status-form">
        @csrf
        <button type="submit" class="attendance__status-change-button attendance__status-change-button--break">休憩戻</button>
    </form>
    @elseif($attendance?->clock_in)
    <div class="attendance__status-change-buttons">
        <form action="/attendance/clock-out" method="post">
            @csrf
            <button type="submit" class="attendance__status-change-button common-btn">退勤</button>
        </form>
        <form action="/attendance/break-in" method="post">
            @csrf
            <button type="submit" class="attendance__status-change-button attendance__status-change-button--break">休憩入</button>
        </form>
    </div>
    @else
    <form action="/attendance/clock-in" method="post" class="attendance__status-form">
        @csrf
        <button type="submit" class="attendance__status-button common-btn">出勤</button>
    </form>
    @endif
</main>
@endsection