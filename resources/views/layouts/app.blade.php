<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Приложение')</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Fira Sans', 'Droid Sans', 'Helvetica Neue', Arial, sans-serif; background: #f5f6f8; color: #1f2937; }
        .container { max-width: 1100px; margin: 0 auto; padding: 16px; }
        .header { position: sticky; top: 0; z-index: 50; background: #fff; border-bottom: 1px solid #e5e7eb; }
        .header-inner { display: flex; align-items: center; justify-content: space-between; height: 56px; }
        .brand { font-weight: 700; color: #111827; text-decoration: none; }
        .user-menu { display: flex; align-items: center; gap: 12px; }
        .user-name { font-size: 14px; color: #374151; }
        .btn { border: 1px solid #d1d5db; background: #fff; color: #111827; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 14px; cursor: pointer; }
        .btn-primary { background: #111827; color: #fff; border-color: #111827; }
        .btn:hover { opacity: .9; }
        .content { padding: 16px 0; }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preload" href="/favicon.ico" as="image">
    @stack('head')
    @stack('styles')
    @stack('scripts-head')
    @yield('head')
    @yield('styles')
    @yield('scripts-head')
    @stack('meta')
    @yield('meta')
    @stack('preloads')
    @yield('preloads')
    @stack('links')
    @yield('links')
    @stack('inline-styles')
    @yield('inline-styles')
</head>
<body>
    <header class="header">
        <div class="container header-inner">
            <a href="{{ route('tasks.index') }}" class="brand">Задачи</a>
            <div class="user-menu">
                @auth
                    <span class="user-name">{{ auth()->user()->name }}</span>
                    <a class="btn" href="{{ route('tasks.index') }}">Мои задачи</a>
                    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="btn">Выйти</button>
                    </form>
                @else
                    <a class="btn" href="{{ route('login') }}">Войти</a>
                    <a class="btn btn-primary" href="{{ route('register') }}">Регистрация</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="container content">
        @yield('content')
    </main>
</body>
</html>

