<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>attend-app</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
    <link rel="stylesheet" href="{{asset('css/common.css')}}">
    <link rel="stylesheet" href="{{asset('css/components.css')}}">
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <h1 class="header__logo">
                @if(Auth::check() && Auth::user()->role === 'admin')
                <a href="http://localhost/admin/attendance/list" class="header__logo-link">
                    <img src="{{asset('images/header.png')}}" alt="" class="header__logo-img">
                </a>
                @else
                <a href="/attendance" class="header__logo-link">
                    <img src="{{asset('images/header.png')}}" alt="" class="header__logo-img">
                </a>
                @endif
            </h1>
            @auth
            <nav class="header__nav">
                <ul class="header__nav-list">
                    @if(Auth::user()->role === 'staff')
                    <li class="header__nav-item">
                        <a href="/attendance" class="header__nav-link">勤怠</a>
                    </li>
                    <li class="header__nav-item">
                        <a href="/attendance/list" class="header__nav-link">勤怠一覧</a>
                    </li>
                    <li class="header__nav-item">
                        <a href="/stamp_correction_request/list" class="header__nav-link">申請</a>
                    </li>
                    @endif
                    @if(Auth::user()->role === 'admin')
                    <li class="header__nav-item">
                        <a href="/admin/attendance/list" class="header__nav-link">勤怠一覧</a>
                    </li>
                    <li class="header__nav-item">
                        <a href="/admin/staff/list" class="header__nav-link">スタッフ一覧</a>
                    </li>
                    <li class="header__nav-item">
                        <a href="/stamp_correction_request/list" class="header__nav-link">申請一覧</a>
                    </li>
                    @endif
                    <li class="header__nav-item">
                        <form action="{{ route('logout') }}" method="post">
                            @csrf
                            <button type="submit" class="header__nav-button">ログアウト</button>
                        </form>
                    </li>
                    @endauth
                </ul>
            </nav>
        </div>
    </header>
    <div class="content">
        @yield('content')
    </div>
</body>

</html>