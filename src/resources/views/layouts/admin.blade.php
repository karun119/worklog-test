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
                @auth
                <a href="{{ route('admin.attendance.list') }}" class="header__logo-link">
                    <img src="{{ asset('images/logo.svg') }}" alt="coachtech" class="header__logo-img">
                </a>
                @else
                <img src="{{ asset('images/logo.svg') }}" alt="coachtech" class="header__logo-img">
                @endauth
            </div>
            @auth
            <nav class="header__nav">
                <ul class="header__nav-list">
                    <li><a href="{{ route('admin.attendance.list') }}" class="header__nav-link">勤怠一覧</a></li>
                    <li><a href="{{ route('admin.staff.list') }}" class="header__nav-link">スタッフ一覧</a></li>
                    <li><a href="{{ route('admin.request.list') }}" class="header__nav-link">申請一覧</a></li>
                    <li>
                        <a href="{{ route('logout') }}" class="header__nav-btn"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            ログアウト
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                            <input type="hidden" name="admin_logout" value="1">
                        </form>
                    </li>
                </ul>
            </nav>
            @endauth
        </div>
    </header>
    <main class="content">
        @yield('content')
    </main>
    @yield('js')
</body>
</html>