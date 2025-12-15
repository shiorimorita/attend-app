@extends('layouts.common')
@section('css')
<link rel="stylesheet" href="{{ asset('css/application_index.css') }}">
@endsection
@section('content')
<main class="application">
    <h2 class="application__title">申請一覧</h2>
    <div class="application__links">
        <a href="" class="application__link">承認待ち</a>
        <a href="" class="application__link">承認済み</a>
    </div>
    <table class="application__table">
        <tr class="application__table-row">
            <th class="application__table-header">状態</th>
            <th class="application__table-header">名前</th>
            <th class="application__table-header">対象日時</th>
            <th class="application__table-header">申請理由</th>
            <th class="application__table-header">申請日時</th>
            <th class="application__table-header">詳細</th>
        </tr>
        <tr class="application__table-row">
            <td class="application__table-detail">承認待ち</td>
            <td class="application__table-detail">西怜奈</td>
            <td class="application__table-detail">2023/06/01</td>
            <td class="application__table-detail">遅延のため</td>
            <td class="application__table-detail">2023/06/02</td>
            <td class="application__table-detail">詳細</td>
        </tr>
    </table>
</main>
@endsection