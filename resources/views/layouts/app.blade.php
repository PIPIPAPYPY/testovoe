<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="@yield('description', 'Система управления задачами')">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<title>@yield('title', 'Приложение')</title>
	
	<!-- Preload критических ресурсов -->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="dns-prefetch" href="//fonts.gstatic.com">
	
	<!-- Инлайн критический CSS для быстрой загрузки -->
	<style>
		/* Критический CSS - минифицированный */
		*{margin:0;padding:0;box-sizing:border-box}
		body{font-family:system-ui,-apple-system,sans-serif;margin:0;background:#f5f5f5;line-height:1.6}
		.container{max-width:1200px;margin:0 auto;padding:20px}
		.navbar{position:sticky;top:0;background:#fff;border-bottom:1px solid #e5e7eb;padding:12px 20px;display:flex;align-items:center;justify-content:space-between;z-index:10}
		.brand{font-weight:700;color:#111827;text-decoration:none;font-size:1.25rem}
		.nav-right{display:flex;align-items:center;gap:12px}
		.avatar{width:32px;height:32px;border-radius:50%;background:#667eea;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:700}
		.link{color:#374151;text-decoration:none;padding:6px 10px;border-radius:6px;transition:background 0.2s}
		.link:hover{background:#f3f4f6}
		.main{padding:20px}
		
		/* Оптимизация для мобильных устройств */
		@media (max-width: 768px) {
			.container{padding:10px}
			.navbar{padding:8px 10px}
			.nav-right{gap:8px}
		}
	</style>
	
	<!-- Отложенная загрузка остального CSS -->
	<style>
		/* Дополнительные стили загружаются после критического */
		.loading{opacity:0.7;pointer-events:none}
		.fade-in{animation:fadeIn 0.3s ease-in}
		@keyframes fadeIn{from{opacity:0}to{opacity:1}}
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
	
	<!-- Оптимизированный JavaScript -->
	<script>
		(function(){
			'use strict';
			
			if('IntersectionObserver' in window){
				const imgObserver=new IntersectionObserver((entries,observer)=>{
					entries.forEach(entry=>{
						if(entry.isIntersecting){
							const img=entry.target;
							img.src=img.dataset.src;
							img.classList.remove('lazy');
							imgObserver.unobserve(img);
						}
					});
				});
				
				document.querySelectorAll('img[data-src]').forEach(img=>{
					imgObserver.observe(img);
				});
			}
			
			document.querySelectorAll('form').forEach(form=>{
				form.addEventListener('submit',function(e){
					const submitBtn=this.querySelector('button[type="submit"]');
					if(submitBtn){
						submitBtn.disabled=true;
						submitBtn.textContent='Загрузка...';
					}
				});
			});
			
			function debounce(func,wait){
				let timeout;
				return function executedFunction(...args){
					const later=()=>{
						clearTimeout(timeout);
						func(...args);
					};
					clearTimeout(timeout);
					timeout=setTimeout(later,wait);
				};
			}
			
			document.querySelectorAll('input[type="text"]').forEach(input=>{
				if(input.name==='search'){
					input.addEventListener('input',debounce(()=>{
					},300));
				}
			});
		})();
	</script>
	
	@yield('scripts')
</body>
</html>









