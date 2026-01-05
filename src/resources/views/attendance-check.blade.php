@extends('layouts.common')
@section('css')
<link rel="stylesheet" href="{{asset('css/attendance-check.css')}}">
@endsection
@section('content')
<main class="attendance__detail">
    <h2 class="attendance__detail-title common-table-title">勤怠詳細</h2>
    <form action="{{ $actionUrl ?? ''}}" class="attendance__form" method="post">
        @csrf
        <div class="attendance__form-inner">
            <div class="attendance__form-group attendance__form-group--display">
                <p class="attendance__form-label attendance__form-label--display">名前</p>
                <div class="attendance__form-value">
                    <span class="attendance__name-label--last">{{ $attendance?->user?->name ?? $user->name }}</span>
                </div>
            </div>
            <div class="attendance__form-group attendance__form-group--display">
                <p class="attendance__form-label attendance__form-label--display">日付</p>
                <div class="attendance__form-value attendance__date-value">
                    <span class="attendance__date-year">{{ $attendance?->date
                        ? \Carbon\Carbon::parse($attendance->date)->format('Y年')
                        : $date->format('Y年') }}</span>
                    <span class="attendance__date-month">{{ $attendance?->date
                        ? \Carbon\Carbon::parse($attendance->date)->format('n月j日')
                        : $date->format('n月j日') }}</span>
                </div>
            </div>
            <div class="attendance__form-group attendance__form-group--input">
                <label for="clock-in" class="attendance__form-label attendance__form-label--input">出勤・退勤</label>
                <div class="attendance__form-value">
                    <input type="text" name="clock_in" class="attendance__input-time attendance__input-in @if($readonly) attendance__input-time--readonly @endif" value="{{ old('clock_in', $clockIn ?? '') }}" @if($readonly) readonly @endif>
                    <span class="attendance__input-separator">～</span>
                    <input type="text" name="clock_out" class="attendance__input-time attendance__input-out @if($readonly) attendance__input-time--readonly @endif" value="{{ old('clock_out', $clockOut ?? '') }}" @if($readonly) readonly @endif>
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
            @foreach($breaks ?? [] as $index => $break)
            <div class="attendance__form-group attendance__form-group--input">
                <label for="break-in-{{$break['id']}}" class="attendance__form-label attendance__form-label--input attendance__form-label-break">休憩{{$index + 1}}</label>
                <div class="attendance__form-value">
                    <input type="text" name="breaks[{{ $break['id'] }}][break_in]" class="attendance__input-time attendance__break-in @if($readonly) attendance__input-time--readonly @endif" value="{{ old('breaks.' . $break['id'] . '.break_in', $break['break_in'] ?? '') }}" id="break-in-{{ $break['id'] }}" @if($readonly) readonly @endif>
                    <span class="attendance__input-separator">～</span>
                    <input type="text" name="breaks[{{ $break['id'] }}][break_out]" class="attendance__input-time attendance__break-out @if($readonly) attendance__input-time--readonly @endif" value="{{ old('breaks.' . $break['id'] . '.break_out', $break['break_out'] ?? '') }}" @if($readonly) readonly @endif>
                </div>
                <p class="attendance__input-error input-error">
                    @error('breaks.' . $break['id'] . '.break_in')
                    {{ $message }}
                    @enderror
                </p>
                <p class="attendance__input-error input-error">
                    @error('breaks.' . $break['id'] . '.break_out')
                    {{ $message }}
                    @enderror
                </p>
            </div>
            @endforeach
            <div class="attendance__form-group attendance__form-group--input">
                <label for="new_break-in" class="attendance__form-label attendance__form-label--input">休憩{{ count($breaks ?? []) + 1 }}</label>
                <div class="attendance__form-value">
                    <input type="text" name="new_break[break_in]" class="attendance__input-time attendance__break-in @if($readonly) attendance__input-time--readonly @endif" value="{{ $newBreak['break_in'] ?? '' }}" id="new_break-in" @if($readonly) readonly @endif>
                    <span class="attendance__input-separator">～</span>
                    <input type="text" name="new_break[break_out]" class="attendance__input-time attendance__break-out @if($readonly) attendance__input-time--readonly @endif" value="{{ $newBreak['break_out'] ?? '' }}" @if($readonly) readonly @endif>
                </div>
            </div>
            <div class="attendance__form-group attendance__form-group--input">
                <label for="description" class="attendance__form-label attendance__form-label--input">備考</label>
                <div class="attendance__form-value">
                    <textarea class="attendance__remark-textarea @if($readonly) attendance__remark-textarea--readonly @endif" name="description" id="description" @if($readonly) readonly @endif>{{ old('description', $descriptionDefault ?? (optional($correction)->description ?? optional($attendance)->description ?? '')) }}</textarea>
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