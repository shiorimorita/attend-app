@extends('layouts.common')
@section('content')
<main class="attendances">
    <h2 class="attendances__title common-table-title">{{request()->routeIs('admin.attendance.staff') ? $user->name . 'さんの勤怠' : '勤怠一覧'}}</h2>
    <x-calendar-pager base-url="/attendance/list" :prev-value="$prevMonth" :next-value="$nextMonth" prev-alt="前月の勤怠" next-alt="翌月の勤怠" input-type="month" input-name="month" :input-value="$month->format('Y-m')" :display-label="$month->format('Y/m')" prevLabel="前月" nextLabel="翌月" />
    <table class="attendances__list-table common-table">
        <tr class="attendances__list-row">
            <th class="attendances__list-header attendances__list-header-date">日付</th>
            <th class="attendances__list-header attendances__list-header--shift">出勤</th>
            <th class="attendances__list-header attendances__list-header--shift">退勤</th>
            <th class="attendances__list-header attendances__list-header--shift">休憩</th>
            <th class="attendances__list-header attendances__list-header--shift">合計</th>
            <th class="attendances__list-header">詳細</th>
        </tr>
        @foreach($days as $day)
        <tr class="attendances__list-row">
            <td class="attendances__list-detail attendances__list-date application__list-detail">{{$day['date']->format('m/d')}}({{$day['date']->isoFormat('ddd')}})</td>
            <td class="attendances__list-detail">{{$day['attendance'] ?->clock_in?->format('H:i') ?? ''}}</td>
            <td class="attendances__list-detail">{{$day['attendance']?->clock_out?->format('H:i') ?? ''}}</td>
            <td class="attendances__list-detail">{{ $day['attendance']?->breakTime() === '0:00' ? '' : $day['attendance']?->breakTime() }}</td>
            <td class="attendances__list-detail">{{ $day['attendance']?->totalTime() ?? '' }}</td>
            <td class="attendances__list-detail">
                @if($day['attendance'])
                <a href="/attendance/detail/{{$day['attendance']->id}}" class="attendances__list-detail-link-text">詳細</a>
                @endif
            </td>
        </tr>
        @endforeach
    </table>
    @if(request()->routeIs('admin.attendance.staff'))
    <form method="get" action="{{ route('attendance.export.csv') }}" class="attendances__csv-form">
        <input type="hidden" name="month" value="{{$month->format('Y-m')}}">
        <input type="hidden" name="user_id" value="{{$user->id}}">
        <button type="submit" class="attendances__csv-button common-btn">CSV出力</button>
    </form>
    @endif
</main>
@endsection