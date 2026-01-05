@extends('layouts.common')
@section('css')
<link rel="stylesheet" href="{{ asset('css/verify-email.css') }}">
@endsection
@section('no-page-bg','no-page-bg')
@section('content')
<main class="verify-email">
    <p class="verify-email__message">登録していただいたメールアドレスに認証メールを送付しました。メール認証を完了してください。</p>
    <a href="{{ env('MAIL_PREVIEW_URL') }}" class="verify-email__button">認証はこちらから</a>
    <form action="{{ route('verification.send') }}" method="post" class="verify-email__form">
        @csrf
        <button type="submit" class="verify-email__link common-link">認証メールを再送する</button>
    </form>
</main>
@endsection