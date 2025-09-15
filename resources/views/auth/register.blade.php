<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - Task Management API</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .register-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            position: relative;
        }

        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 20px 20px 0 0;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
            margin-top: 10px;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .response {
            margin-top: 15px;
            padding: 12px;
            border-radius: 8px;
            font-size: 0.9rem;
            display: none;
        }

        .response.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .response.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .back-link {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <a href="/" class="back-link">← Назад на главную</a>
    
    <div class="register-container">
        <div class="header">
            <h1>📝 Регистрация</h1>
            <p>Создайте новую учетную запись</p>
        </div>

        <form id="registerForm">
            <div class="form-group">
                <label for="name">Имя</label>
                <input type="text" id="name" name="name" required placeholder="Ваше имя">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required placeholder="your@email.com">
            </div>

            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" required placeholder="••••••••" minlength="6">
            </div>

            <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
        </form>

        <div id="response" class="response"></div>

        <div class="links">
            <p>Уже есть аккаунт? <a href="/login">Войти</a></p>
        </div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const responseEl = document.getElementById('response');
            
            try {
                const response = await fetch('/api/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ name, email, password })
                });
                
                const data = await response.json();
                
                responseEl.style.display = 'block';
                responseEl.className = `response ${response.ok ? 'success' : 'error'}`;
                
                if (response.ok) {
                    responseEl.textContent = 'Регистрация успешна! Перенаправление...';
                    localStorage.setItem('token', data.token);
                    setTimeout(() => {
                        window.location.href = '/tasks';
                    }, 1500);
                } else {
                    responseEl.textContent = data.message || 'Ошибка регистрации';
                }
            } catch (error) {
                responseEl.style.display = 'block';
                responseEl.className = 'response error';
                responseEl.textContent = 'Ошибка соединения';
            }
        });
    </script>
</body>
</html>












