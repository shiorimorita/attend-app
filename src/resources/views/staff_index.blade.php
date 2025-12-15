@extends('layouts.common')
@section('css')
<link rel="stylesheet" href="{{asset('css/staff_index.css')}}">
@endsection
@section('content')
<main class="staff__list">
    <h2 class="staff__list-title">スタッフ一覧</h2>
    <table class="staff__list-table">
        <tr class="staff__list-row">
            <th class="staff__list-header">名前</th>
            <th class="staff__list-header">メールアドレス</th>
            <th class="staff__list-header">月次勤怠</th>
        </tr>
        <tr class="staff__list-row">
            <td class="staff__list-detail">西&nbsp;怜奈</td>
            <td class="staff__list-detail">reina.n@coachtech.com</td>
            <td class="staff__list-detail">詳細</td>
        </tr>
    </table>
</main>
@endsection