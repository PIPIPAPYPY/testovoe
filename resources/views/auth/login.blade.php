<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в аккаунт</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            max-width: 450px;
            width: 100%;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        .login-container::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-size: 2rem; font-weight: 700; color: #333; margin-bottom: 10px; }
        .header p { color: #666; font-size: 1rem; }
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
            font-size: 0.9rem;
        }
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            background: #f9fafb;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .remember-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 25px 0;
        }
        .remember-group label { display: flex; align-items: center; gap: 8px; color: #666; font-weight: 500; cursor: pointer; }
        .remember-group input[type="checkbox"] { width: auto; margin: 0; }
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        .error-message {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            color: #dc2626;
            border: 1px solid #fecaca;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .register-link { text-align: center; margin-top: 25px; color: #666; }
        .register-link a { color: #667eea; text-decoration: none; font-weight: 600; }
        .register-link a:hover { text-decoration: underline; }
        @media (max-width: 480px) {
            .login-container { padding: 30px 20px; }
            .header h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
<div class="login-container">
    <a href="/" class="back-btn">← Назад на главную</a>
    <div class="header">
        <h1>🔐 Вход в аккаунт</h1>
        <p>Войдите в свой аккаунт для доступа к задачам</p>
    </div>

    @if ($errors->any())
        <div class="error-message">
            {{ $errors->first() }}
        </div>
    @endif

    <form id="loginForm" method="POST" action="{{ route('login') }}">
        @csrf
        <div class="form-group">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="Введите ваш email">
        </div>
        <div class="form-group">
            <label for="password">Пароль</label>
            <input id="password" type="password" name="password" required placeholder="Введите ваш пароль">
        </div>
        <div class="remember-group">
            <label>
                <input type="checkbox" name="remember"> Запомнить меня
            </label>
        </div>
        <button type="submit" class="btn">Войти в аккаунт</button>
    </form>

    <div class="register-link">
        <p>Нет аккаунта? <a href="/register">Зарегистрироваться</a></p>
    </div>
</div>

{{-- Форма теперь работает с веб-сессиями, JavaScript не нужен --}}
</body>
</html>
