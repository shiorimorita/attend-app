@extends('layouts.common')
@section('css')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection
@section('no-page-bg','no-page-bg')
@section('content')
<main class="login">
    <h2 class="login__title">ログイン</h2>
    <form action="/login" method="post" class="login__form">
        @csrf
        <input type="hidden" name="login_type" value="{{request()->is('admin/login') ? 'admin' : 'staff'}}">
        <div class="login__group">
            <label for="email" class="login__label">メールアドレス</label>
            <input type="mail" name="email" id="email" class="login__input" value="{{ old('email') }}">
            <p class="input-error">
                @error('email')
                {{ $message }}
                @enderror
            </p>
        </div>
        <div class="login__group">
            <label for="password" class="login__label">パスワード</label>
            <input type="password" name="password" id="password" class="login__input">
            <p class="input-error">
                @error('password')
                {{ $message }}
                @enderror
            </p>
        </div>
        <div class="login__buttons">
            <button type="submit" class="login__button common-btn">ログインする</button>
            @if(request()->is('login'))
            <a href="/register" class="login__link common-link">会員登録はこちら</a>
            @endif
        </div>
    </form>
</main>
@endsection