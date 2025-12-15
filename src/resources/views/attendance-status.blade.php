@extends('layouts.common')
@section('css')
<link rel="stylesheet" href="{{asset('css/attendance-status.css')}}">
@endsection
@section('content')
<main class="attendance__status">
    <form action="" method="post" class="attendance__status-form">
        @csrf
        <p class="attendance__status-text">勤務外</p>
        <p class="attendance__status-date">2023年6月1日(木)</p>
        <p class="attendance__status-time">08:00</p>
        <button type="submit" class="attendance__status-button common-btn">出勤</button>
    </form>
</main>
@endsection