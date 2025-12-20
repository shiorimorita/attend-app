@extends('layouts.common')
@section('css')
<link rel="stylesheet" href="{{asset('css/staff-index.css')}}">
@endsection
@section('content')
<main class="staff__list">
    <h2 class="staff__list-title common-table-title">スタッフ一覧</h2>
    <table class="common-table">
        <tr class="staff__list-row">
            <th class="staff__list-header">名前</th>
            <th class="staff__list-header">メールアドレス</th>
            <th class="staff__list-header">月次勤怠</th>
        </tr>
        @foreach($users as $user)
        <tr class="staff__list-row">
            <td class="staff__list-detail">{{$user->name}}</td>
            <td class="staff__list-detail">{{$user->email}}</td>
            <td class="staff__list-detail">
                <a href="/admin/attendance/staff/{{$user->id}}">詳細</a>
            </td>
        </tr>
        @endforeach
    </table>
</main>
@endsection