@extends('layouts.common')
@section('css')
<link rel="stylesheet" href="{{asset('css/attendance-index.css')}}">
@endsection
@section('content')
<main class="attendances">
    <h2 class="attendances__title common-table-title">勤怠一覧</h2>
    <div class="attendances__pager">
        <a href="/attendance/list?month={{$prevMonth}}" class="attendances__pager-prev">
            <img src="{{asset('images/arrow-left.png')}}" alt="前月の勤怠" class="attendances__pager-prev-img">
            <p class="attendances__pager-prev-label">前月</p>
        </a>
        <form action="/attendance/list" method="get" class="attendances__pager-current">
            <img src="{{asset('images/calendar.png')}}" alt="カレンダー" class="attendances__pager-calendar-img" onclick="document.querySelector('.attendances__pager-current-input').showPicker()">
            <input type="month" name="month" class="attendances__pager-current-input" onchange="this.form.submit()" value="{{$month->format('Y:m')}}">
            <span class="attendances__pager-current-label">{{$month->format('Y/m')}}</span>
        </form>
        <a href="/attendance/list?month={{$nextMonth}}" class="attendances__pager-next">
            <p class="attendances__pager-next-label">翌月</p>
            <img src="{{asset('images/arrow-right.png')}}" alt="翌月の勤怠" class="attendances__pager-next-img">
        </a>
    </div>
    <table class="common-table">
        <tr class="attendances__list-row">
            <th class="attendances__list-header attendances__list-header-date application__list-header">日付</th>
            <th class="attendances__list-header attendances__list-header--shift">出勤</th>
            <th class="attendances__list-header attendances__list-header--shift">退勤</th>
            <th class="attendances__list-header attendances__list-header--shift">休憩</th>
            <th class="attendances__list-header attendances__list-header--shift">合計</th>
            <th class="attendances__list-header">詳細</th>
        </tr>
        @foreach($days as $day)
        <tr class="attendances__list-row">
            <td class="attendances__list-detail attendances__list-date application__list-detail">{{$day['date']->format('m/d')}}({{$day['date']->isoFormat('ddd')}})</td>
            <td class="attendances__list-detail">{{$day['attendance']?->clock_in ? \Carbon\Carbon::parse($day['attendance']->clock_in)->format('H:i') : '' }}</td>
            <td class="attendances__list-detail">{{$day['attendance']?->clock_out ? \Carbon\Carbon::parse($day['attendance']->clock_out)->format('H:i') : ''}}</td>
            <td class="attendances__list-detail">{{$day['attendance']?->breakTime() ?? ''}}</td>
            <td class="attendances__list-detail">{{$day['attendance']?->totalTime() ?? ''}}</td>
            <td class="attendances__list-detail">
                @if($day['attendance'])
                <a href="/attendance/detail/{{$day['attendance']->id}}" class="attendances__list-detail-link-text">詳細</a>
                @else
                <span class="attendances__list-detail-link-text">詳細</span>
                @endif
            </td>
        </tr>
        @endforeach
    </table>
</main>
@endsection