<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>attend-app</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
    <link rel="stylesheet" href="{{asset('css/common.css')}}">
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <h1 class="header__logo">
                <a href="/" class="header__logo-link">
                    <img src="{{asset('images/header.png')}}" alt="" class="header__logo-img">
                </a>
            </h1>
            <nav class="header__nav">
                <ul class="header__nav-list">
                    <li class="header__nav-item">
                        <a href="" class="header__nav-link">勤怠</a>
                    </li>
                    <li class="header__nav-item">
                        <a href="" class="header__nav-link">勤怠一覧</a>
                    </li>
                    <li class="header__nav-item">
                        <a href="" class="header__nav-link">申請</a>
                    </li>
                    @if(Auth::check())
                    <li class="header__nav-item">
                        <form action="/logout" method="post" class="header__logout-form">
                            <button type="submit" class="header__nav-button">ログアウト</button>
                        </form>
                    </li>
                    @else
                    <li class="header__nav-item">
                        <a href="/login" class="header__nav-link">ログイン</a>
                    </li>
                    @endif
                </ul>
            </nav>
        </div>
    </header>
    <div class="content">
        @yield('content')
    </div>
</body>

</html>