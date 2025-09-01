<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>@yield('title', 'Приложение')</title>
	<style>
		body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background-color: #f5f5f5; }
		.container { max-width: 1200px; margin: 0 auto; padding: 30px; }
		.navbar { position: sticky; top: 0; background: #ffffff; border-bottom: 1px solid #e5e7eb; padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; z-index: 10; }
		.brand { font-weight: 700; color: #111827; text-decoration: none; }
		.nav-right { display: flex; align-items: center; gap: 12px; }
		.avatar { width: 32px; height: 32px; border-radius: 9999px; background: #667eea; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; }
		.link { color: #374151; text-decoration: none; padding: 6px 10px; border-radius: 6px; }
		.link:hover { background: #f3f4f6; }
		.main { padding: 20px; }
	</style>
</head>
<body>
	<nav class="navbar">
		<a href="/" class="brand">Задачи</a>
		<div class="nav-right">
			@auth
				<span class="avatar">{{ mb_substr(auth()->user()->name ?? 'U', 0, 1) }}</span>
				<span>{{ auth()->user()->name }}</span>
				<a class="link" href="{{ route('tasks.index') }}">Мои задачи</a>
				<form method="POST" action="{{ route('logout') }}" style="display:inline">
					@csrf
					<button type="submit" class="link" style="background:none;border:none;cursor:pointer">Выйти</button>
				</form>
			@else
				<a class="link" href="{{ route('login') }}">Войти</a>
			@endauth
		</div>
	</nav>
	<main class="main container">
		@yield('content')
	</main>
</body>
</html>
