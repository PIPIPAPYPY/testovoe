@extends('layouts.app')

@section('title', 'Вход')

@section('content')
<div style="max-width:420px;margin:0 auto;background:#fff;padding:24px;border-radius:12px;box-shadow:0 10px 20px rgba(0,0,0,0.08)">
	<h2 style="margin:0 0 16px 0;color:#111827">Вход в аккаунт</h2>
	@if ($errors->any())
		<div style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;padding:12px;border-radius:8px;margin-bottom:12px">
			{{ $errors->first() }}
		</div>
	@endif
	<form method="POST" action="{{ route('login') }}">
		@csrf
		<div style="margin-bottom:12px">
			<label for="email" style="display:block;margin-bottom:6px;color:#374151">Email</label>
			<input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px">
		</div>
		<div style="margin-bottom:12px">
			<label for="password" style="display:block;margin-bottom:6px;color:#374151">Пароль</label>
			<input id="password" type="password" name="password" required style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px">
		</div>
		<div style="display:flex;align-items:center;justify-content:space-between;margin-top:16px">
			<label style="display:flex;align-items:center;gap:8px;color:#374151"><input type="checkbox" name="remember"> Запомнить</label>
			<button type="submit" style="background:#4f46e5;color:#fff;border:none;padding:10px 16px;border-radius:8px;cursor:pointer">Войти</button>
		</div>
	</form>
</div>
@endsection

