@extends('layouts.common')
@section('css')
<link rel="stylesheet" href="{{asset('css/attendance-check.css')}}">
@endsection
@section('content')
<main class="attendance__detail">
    <h2 class="attendance__detail-title common-table-title">勤怠詳細</h2>
    <form action="{{
    $mode === 'admin'
      ? ($correction
          ? url('/stamp_correction_request/approve/' . $correction->id)
          : url('/admin/attendance/detail/' . $attendance->id)
        )
      : url('/attendance/detail/' . $attendance->id)
    }}" class="attendance__form" method="post">
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
                    <input type="text" name="clock_in" class="attendance__input-time attendance__input-in" value="{{ old(
                            'clock_in',
                            $correction
                                ? \Carbon\Carbon::parse($correction->clock_in)->format('H:i')
                                : ($attendance->clock_in
                                    ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i')
                                    : '')
                        ) }}">
                    <span class="attendance__input-separator">～</span>
                    <input type="text" name="clock_out" class="attendance__input-time attendance__input-out" value="{{ old(
                            'clock_out',
                            $correction
                                ? \Carbon\Carbon::parse($correction->clock_out)->format('H:i')
                                : ($attendance->clock_out
                                    ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i')
                                    : '')
                        ) }}">
                </div>
                <p class="attendance__input-error input-error">
                    @error('clock_in')
                    {{ $message }}
                    @enderror
                    @error('clock_out')
                    {{ $message }}
                    @enderror
                </p>
            </div>
            @foreach($attendance->breaks as $index => $break)
            @php
            $breakCorrection = $correction && $correction->breakCorrections
            ? $correction->breakCorrections->firstWhere('attendance_break_id', $break->id)
            : null;
            $isDeleted = $breakCorrection && $breakCorrection->is_deleted;
            @endphp
            <div class="attendance__form-group attendance__form-group--input">
                <label for="break-in-{{$break->id}}" class="attendance__form-label attendance__form-label--input attendance__form-label-break">休憩{{$index + 1}}</label>
                <div class="attendance__form-value">
                    <input type="text" name="breaks[{{$break->id}}][break_in]" class="attendance__input-time attendance__break-in" value="{{ old(
                        'breaks.'.$break->id.'.break_in',
                        $isDeleted
                            ? ''
                            : ($breakCorrection && $breakCorrection->break_in
                                ? \Carbon\Carbon::parse($breakCorrection->break_in)->format('H:i')
                                : ($break->break_in
                                    ? \Carbon\Carbon::parse($break->break_in)->format('H:i')
                                    : '')
                            )
                    ) }}" id="break-in-{{ $break->id }}">
                    <span class="attendance__input-separator">～</span>
                    <input type="text" name="breaks[{{ $break->id }}][break_out]" class="attendance__input-time attendance__break-out" value="{{ old(
                        'breaks.'.$break->id.'.break_out',
                        $isDeleted
                            ? ''
                            : ($breakCorrection && $breakCorrection->break_out
                                ? \Carbon\Carbon::parse($breakCorrection->break_out)->format('H:i')
                                : ($break->break_out
                                    ? \Carbon\Carbon::parse($break->break_out)->format('H:i')
                                    : '')
                            )
                    ) }}">
                </div>
                <p class="attendance__input-error input-error">
                    @error('breaks.' . $break->id . '.break_in')
                    {{ $message }}
                    @enderror
                </p>
                <p class="attendance__input-error input-error">
                    @error('breaks.' . $break->id . '.break_out')
                    {{ $message }}
                    @enderror
                </p>
            </div>
            @endforeach
            @php
            $newBreakCorrection = $correction && $correction->breakCorrections
            ? $correction->breakCorrections->firstWhere('attendance_break_id', null)
            : null;
            @endphp
            <div class="attendance__form-group attendance__form-group--input">
                <label for="new_break-in" class="attendance__form-label attendance__form-label--input">休憩{{ $attendance->breaks->count() + 1 }}</label>
                <div class="attendance__form-value">
                    <input type="text" name="new_break[break_in]" class="attendance__input-time attendance__break-in" value="{{ old(
                        'new_break.break_in',
                        $newBreakCorrection
                            ? \Carbon\Carbon::parse($newBreakCorrection->break_in)->format('H:i')
                            : ''
                    ) }}" id="new_break-in">
                    <span class="attendance__input-separator">～</span>
                    <input type="text" name="new_break[break_out]" class="attendance__input-time attendance__break-out" value="{{ old(
                        'new_break.break_out',
                        $newBreakCorrection
                            ? \Carbon\Carbon::parse($newBreakCorrection->break_out)->format('H:i')
                            : ''
                    ) }}">
                </div>
            </div>
            <div class="attendance__form-group attendance__form-group--input">
                <label for="description" class="attendance__form-label attendance__form-label--input">備考</label>
                <div class="attendance__form-value">
                    <textarea class="attendance__remark-textarea" name="description" id="description">{{ old('description', optional($correction)->description) }}</textarea>
                </div>
                <span class="attendance__input-error input-error">
                    @error('description')
                    {{ $message }}
                    @enderror
                </span>
            </div>
        </div>
        @php
        $user = auth()->user();
        @endphp
        {{-- staff --}}
        @if (! $user->isAdmin())
        @if ($correction && $correction->status === 'pending')
        <p class="attendance__notice">
            *承認待ちのため修正はできません。
        </p>
        @else
        <button type="submit" class="attendance__form-submit common-btn">
            修正
        </button>
        @endif
        {{-- admin --}}
        @else
        @if($correction && $correction->status === 'approved')
        <button type="button" disabled class="attendance__form-submit attendance__button--disabled">
            承認済み
        </button>
        @else
        <button type="submit" class="attendance__form-submit common-btn">
            修正
        </button>
        @endif
        @endif
    </form>
</main>
@endsection