@extends('layouts.common')
@section('css')
<link rel="stylesheet" href="{{asset('css/attendance-check.css')}}">
@endsection
@section('content')
<main class="attendance__detail">
    <h2 class="attendance__detail-title common-table-title">勤怠詳細</h2>
    <form action="/attendance/detail/{{$attendance->id}}" class="attendance__form" method="post">
        @csrf
        <div class="attendance__form-inner">
            <div class="attendance__form-group attendance__form-group--display">
                <p class="attendance__form-label attendance__form-label--display">名前</p>
                <div class="attendance__form-value">
                    <span class="attendance__name-label--last">{{$attendance->user->name}}</span>
                </div>
            </div>
            <div class="attendance__form-group attendance__form-group--display">
                <p class="attendance__form-label attendance__form-label--display">日付</p>
                <div class="attendance__form-value attendance__date-value">
                    <span class="attendance__date-year">{{\Carbon\Carbon::parse($attendance['date'])->format('Y年')}}</span>
                    <span class="attendance__date-month">{{\Carbon\Carbon::parse($attendance['date'])->format('n月j日')}}</span>
                </div>
            </div>
            <div class="attendance__form-group attendance__form-group--input">
                <label for="clock-in" class="attendance__form-label attendance__form-label--input">出勤・退勤</label>
                <div class="attendance__form-value">
                    <input type="text" name="clock_in" class="attendance__input-time attendance__input-in" value="{{old('clock_in',$attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '')}}" id="clock-in">
                    <span class="attendance__input-separator">～</span>
                    <input type="text" name="clock_out" class="attendance__input-time attendance__input-out" value="{{old('clock_out',$attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '')}}">
                </div>
            </div>
            @foreach($attendance->breaks as $index => $break)
            <div class="attendance__form-group attendance__form-group--input">
                <label for="break-in-{{$break->id}}" class="attendance__form-label attendance__form-label--input attendance__form-label-break">休憩{{$index + 1}}</label>
                <div class="attendance__form-value">
                    <input type="text" name="breaks[{{$break->id}}][break_in]" class="attendance__input-time attendance__break-in" value="{{ old('breaks.'.$break->id.'.break_in', $break->break_in ? \Carbon\Carbon::parse($break->break_in)->format('H:i') : '') }}" id="break-in-{{ $break->id }}">
                    <span class="attendance__input-separator">～</span>
                    <input type="text" name="breaks[{{ $break->id }}][break_out]" class="attendance__input-time attendance__break-out" value="{{ old('breaks.'.$break->id.'.break_out', $break->break_out ? \Carbon\Carbon::parse($break->break_out)->format('H:i') : '') }}">
                </div>
            </div>
            @endforeach
            <div class="attendance__form-group attendance__form-group--input">
                <label for="new_break-in" class="attendance__form-label attendance__form-label--input">休憩{{ $attendance->breaks->count() + 1 }}</label>
                <div class="attendance__form-value">
                    <input type="text" name="new_break[break_in]" class="attendance__input-time attendance__break-in" value="{{old('new_break.break_in')}}" id="new_break-in">
                    <span class="attendance__input-separator">～</span>
                    <input type="text" name="new_break[break_out]" class="attendance__input-time attendance__break-out" value="{{old('new_break.break_out')}}">
                </div>
            </div>
            <div class="attendance__form-group attendance__form-group--input">
                <label for="description" class="attendance__form-label attendance__form-label--input">備考</label>
                <div class="attendance__form-value">
                    <textarea class="attendance__remark-textarea" name="description" id="description"></textarea>
                </div>
            </div>
        </div>
        @if ($attendance->attendanceCorrections()->exists())
        <p class="attendance__notice">*承認待ちのため修正はできません。</p>
        @else
        <button type="submit" class="attendance__form-submit common-btn">修正</button>
        @endif
    </form>
</main>
@endsection