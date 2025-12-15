@extends('layouts.common')
@section('css')
<link rel="stylesheet" href="{{asset('css/detail.css')}}">
@endsection
@section('content')
<main class="attendance__detail">
    <h2 class="attendance__detail-title">勤怠詳細</h2>
    <form class="attendance__form" method="post" action="">
        @csrf
        <div class="attendance__form-inner">
            <div class="attendance__form-group attendance__form-group--display">
                <p class="attendance__form-label attendance__form-label--display">名前</p>
                <div class="attendance__form-value">
                    <span class="attendance__name-label--last">西</span><span class="attendance__name-space"></span><span class="attendance__name-label--first">怜奈</span>
                </div>
            </div>
            <div class="attendance__form-group attendance__form-group--display">
                <p class="attendance__form-label attendance__form-label--display">日付</p>
                <div class="attendance__form-value attendance__date-value">
                    <span class="attendance__date-year">2023年</span>
                    <span class="attendance__date-month">6月1日</span>
                </div>
            </div>
            <div class="attendance__form-group attendance__form-group--input">
                <label for="clock-in" class="attendance__form-label attendance__form-label--input">出勤・退勤</label>
                <div class="attendance__form-value">
                    <input type="text" class="attendance__input-time attendance__input-in" value="09:00" id="clock-in">
                    <span class="attendance__input-separator">～</span>
                    <input type="text" class="attendance__input-time attendance__input-out" value="18:00">
                </div>
            </div>
            <div class="attendance__form-group attendance__form-group--input">
                <label for="break1-in" class="attendance__form-label attendance__form-label--input">休憩</label>
                <div class="attendance__form-value">
                    <input type="text" class="attendance__input-time attendance__break-in" value="12:00" id="break1-in">
                    <span class="attendance__input-separator">～</span>
                    <input type="text" class="attendance__input-time attendance__break-out" value="13:00">
                </div>
            </div>
            <div class="attendance__form-group attendance__form-group--input">
                <label for="break2-in" class="attendance__form-label attendance__form-label--input">休憩2</label>
                <div class="attendance__form-value">
                    <input type="text" class="attendance__input-time attendance__break-in" value="" id="break2-in">
                    <span class="attendance__input-separator">～</span>
                    <input type="text" class="attendance__input-time attendance__break-out" value="">
                </div>
            </div>
            <div class="attendance__form-group attendance__form-group--input">
                <label for="description" class="attendance__form-label attendance__form-label--input">備考</label>
                <div class="attendance__form-value">
                    <textarea class="attendance__remark-textarea" name="description" id="description"></textarea>
                </div>
            </div>
        </div>
        <button type="submit" class="attendance__form-submit common-btn">修正</button>
    </form>
</main>
@endsection