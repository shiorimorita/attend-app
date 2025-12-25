@extends('layouts.common')
@section('css')
<link rel="stylesheet" href="{{ asset('css/application-index.css') }}">
@endsection
@section('content')
<main class="application">
    <h2 class="application__title common-table-title">申請一覧</h2>
    <div class="application__links">
        <a href="/stamp_correction_request/list?status=pending" class="application__link {{$status ==='pending' ? 'is-active' : ''}}">承認待ち</a>
        <a href="/stamp_correction_request/list?status=approved" class="application__link {{$status ==='approved' ? 'is-active' : ''}}">承認済み</a>
    </div>
    <table class="common-table">
        <tr class="application__table-row">
            <th class="application__table-header">状態</th>
            <th class="application__table-header">名前</th>
            <th class="application__table-header">対象日時</th>
            <th class="application__table-header">申請理由</th>
            <th class="application__table-header">申請日時</th>
            <th class="application__table-header">詳細</th>
        </tr>
        @foreach($corrections as $correction)
        @if($correction->request_type === 'admin_edit')
        @continue
        @endif
        <tr class="application__table-row">
            <td class="application__table-detail">{{($correction->status)==='pending' ? '承認待ち' : (($correction->status === 'approved' && $correction->request_type === 'staff_request') ? '承認済み' : '')}}</td>
            <td class="application__table-detail">{{$correction->user->name}}</td>
            <td class="application__table-detail">{{$correction->attendance->date->format('Y/m/d') }}</td>
            <td class="application__table-detail">{{$correction->description}}</td>
            <td class="application__table-detail">{{$correction->created_at->format('Y/m/d')}}</td>
            <td class="application__table-detail">
                @if(Auth::user()->isAdmin())
                <a href="{{route('stamp_correction_request.approve', ['attendance_correct_request_id' => $correction->id])}}" class="attendances__list-detail-link-text">詳細</a>
                @else
                <a href="{{route('attendance.detail',['id' =>$correction->attendance->id])}}" class="attendances__list-detail-link-text">詳細</a>
                @endif
            </td>
        </tr>
        @endforeach
    </table>
</main>
@endsection