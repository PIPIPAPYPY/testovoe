@extends('layouts.app')

@section('title', 'Вход')

@section('content')
    <div style="max-width:420px;margin:24px auto;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:20px;">
        <h1 style="margin:0 0 16px;font-size:20px;">Вход</h1>
        @if ($errors->any())
            <div style="background:#fee2e2;color:#991b1b;border:1px solid #fecaca;padding:10px;border-radius:6px;margin-bottom:12px;">
                {{ $errors->first() }}
            </div>
        @endif
        <form method="POST" action="{{ route('login.post') }}">
            @csrf
            <div style="margin-bottom:12px;">
                <label for="email" style="display:block;font-size:12px;color:#6b7280;margin-bottom:6px;">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" autofocus style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:6px;">
            </div>
            <div style="margin-bottom:12px;">
                <label for="password" style="display:block;font-size:12px;color:#6b7280;margin-bottom:6px;">Пароль</label>
                <input id="password" name="password" type="password" required autocomplete="current-password" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:6px;">
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:16px;">
                <label style="display:flex;align-items:center;gap:8px;font-size:12px;color:#374151;">
                    <input type="checkbox" name="remember"> Запомнить меня
                </label>
                <button type="submit" class="btn btn-primary">Войти</button>
            </div>
        </form>
    </div>
@endsection

