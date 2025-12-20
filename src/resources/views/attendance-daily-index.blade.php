@extends('layouts.common')
@section('content')
<main class="attendances">
    <h2 class="attendances__title common-table-title">{{$date->format('Y年n月j日')}}</h2>
    <x-calendar-pager base-url="/admin/attendance/list" :prev-value="$prevDate" :next-value="$nextDate" prev-alt="前日の勤怠" next-alt="翌日の勤怠" input-type="date" input-name="date" :input-value="$date->format('Y-m-d')" :display-label="$date->format('Y/m/d')" prevLabel="前日" nextLabel="翌日" />
    <table class="common-table">
        <tr class="attendances__list-row">
            <th class="attendances__list-header attendances__list-header-name">名前</th>
            <th class="attendances__list-header attendances__list-header--shift">出勤</th>
            <th class="attendances__list-header attendances__list-header--shift">退勤</th>
            <th class="attendances__list-header attendances__list-header--shift">休憩</th>
            <th class="attendances__list-header attendances__list-header--shift">合計</th>
            <th class="attendances__list-header">詳細</th>
        </tr>
        @foreach($users as $user)
        <tr class="attendances__list-row">
            <td class="attendances__list-detail attendances__list-name">{{$user->name}}</td>
            <td class="attendances__list-detail">{{optional($user->dailyAttendance)->clock_in?->format('H:i')}}</td>
            <td class="attendances__list-detail">{{optional($user->dailyAttendance)->clock_out?->format('H:i')}}</td>
            <td class="attendances__list-detail">{{ optional($user->dailyAttendance)->breakTime() === '0:00' ? '' : optional($user->dailyAttendance)->breakTime() }}</td>
            <td class="attendances__list-detail">{{optional($user->dailyAttendance)->totalTime() ?? ''}}</td>
            <td class="attendances__list-detail">
                @if($user->dailyAttendance)
                <a href="/admin/attendance/detail/{{ $user->dailyAttendance->id }}" class="attendances__list-detail-link-text">詳細</a>
                @endif
            </td>
        </tr>
        @endforeach
    </table>
</main>
@endsection