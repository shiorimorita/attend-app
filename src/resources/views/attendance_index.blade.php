@extends('layouts.common')
@section('css')
<link rel="stylesheet" href="{{asset('css/attendance_index.css')}}">
@endsection
@section('content')
<main class="attendances">
    <h2 class="attendances__title">勤怠一覧</h2>
    <div class="attendances__pager">
        <div class="attendances__pager-prev">
            <img src="{{asset('images/arrow-left.png')}}" alt="前月の勤怠" class="attendances__pager-prev-img">
            <p class="attendances__pager-prev-label">前月</p>
        </div>
        <div class="attendances__pager-current">
            <img src="{{asset('images/calendar.png')}}" alt="カレンダー" class="attendances__pager-calendar-img">
            <span class="attendances__pager-current-label">2023/06</span>
        </div>
        <div class="attendances__pager-next">
            <p class="attendances__pager-next-label">翌月</p>
            <img src="{{asset('images/arrow-right.png')}}" alt="翌月の勤怠" class="attendances__pager-next-img">
        </div>
    </div>
    <table class="attendances__list">
        <tr class="attendances__list-row">
            <th class="attendances__list-header attendances__list-header-date application__list-header">日付</th>
            <th class="attendances__list-header attendances__list-header--shift">出勤</th>
            <th class="attendances__list-header attendances__list-header--shift">退勤</th>
            <th class="attendances__list-header attendances__list-header--shift">休憩</th>
            <th class="attendances__list-header attendances__list-header--shift">合計</th>
            <th class="attendances__list-header">詳細</th>
        </tr>
        <tr class="attendances__list-row">
            <td class="attendances__list-detail attendances__list-date application__list-detail">06/03(土)</td>
            <td class="attendances__list-detail">09:00</td>
            <td class="attendances__list-detail">18:00</td>
            <td class="attendances__list-detail">1:00</td>
            <td class="attendances__list-detail">8:00</td>
            <td class="attendances__list-detail">
                <a href="#" class="attendances__list-detail-link-text">詳細</a>
            </td>
        </tr>
    </table>
</main>
@endsection