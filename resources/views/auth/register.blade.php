@extends('layouts.app')

@section('title', 'Регистрация')

@section('content')
    <div style="max-width:420px;margin:24px auto;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:20px;">
        <h1 style="margin:0 0 16px;font-size:20px;">Регистрация</h1>
        @if ($errors->any())
            <div style="background:#fee2e2;color:#991b1b;border:1px solid #fecaca;padding:10px;border-radius:6px;margin-bottom:12px;">
                {{ $errors->first() }}
            </div>
        @endif
        <form method="POST" action="{{ route('register.post') }}">
            @csrf
            <div style="margin-bottom:12px;">
                <label for="name" style="display:block;font-size:12px;color:#6b7280;margin-bottom:6px;">Имя</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:6px;">
            </div>
            <div style="margin-bottom:12px;">
                <label for="email" style="display:block;font-size:12px;color:#6b7280;margin-bottom:6px;">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:6px;">
            </div>
            <div style="margin-bottom:12px;">
                <label for="password" style="display:block;font-size:12px;color:#6b7280;margin-bottom:6px;">Пароль</label>
                <input id="password" name="password" type="password" required style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:6px;">
            </div>
            <div style="margin-bottom:12px;">
                <label for="password_confirmation" style="display:block;font-size:12px;color:#6b7280;margin-bottom:6px;">Повторите пароль</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:6px;">
            </div>
            <div style="display:flex;align-items:center;justify-content:flex-end;margin-top:16px;">
                <button type="submit" class="btn btn-primary">Создать аккаунт</button>
            </div>
        </form>
    </div>
@endsection

