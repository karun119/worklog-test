<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>勤怠管理</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/common.css') }}" />
    @yield('css')
</head>
<body>
    <header class="header">
        <div class="header__container">
            <div class="header__logo">
                @if(auth()->check() && auth()->user()->hasVerifiedEmail())
                    <a href="{{ url('/attendance') }}" class="header__logo-link">
                        <img src="{{ asset('images/logo.svg') }}" alt="coachtech" class="header__logo-img">
                    </a>
                @else
                    <img src="{{ asset('images/logo.svg') }}" alt="coachtech" class="header__logo-img">
                @endif
            </div>
            @auth
                @if(auth()->user()->hasVerifiedEmail())
                    <nav class="header__nav">
                        <ul class="header__nav-list">
                            @php
                                $userStatus = auth()->user()->attendance_status ?? 'before_work';
                            @endphp

                            @if($userStatus === 'after_work')
                                <li><a href="{{ route('attendance.list') }}" class="header__nav-link">今月の出勤一覧</a></li>
                                <li><a href="{{ route('stamp.request.list') }}" class="header__nav-link">申請一覧</a></li>
                            @else
                                <li><a href="{{ route('attendance.index') }}" class="header__nav-link">勤怠</a></li>
                                <li><a href="{{ route('attendance.list') }}" class="header__nav-link">勤怠一覧</a></li>
                                <li><a href="{{ route('stamp.request.list') }}" class="header__nav-link">申請</a></li>
                            @endif
                            <li>
                                <a href="{{ route('logout') }}" class="header__nav-btn"
                                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    ログアウト
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                @endif
            @endauth
        </div>
    </header>
    <main class="content">
        @yield('content')
    </main>
    <footer class="footer">
        @yield('link')
    </footer>
    @yield('js')
</body>
</html>