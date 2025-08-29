<!DOCTYPE html>
<html lang="ru">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management API - Laravel</title>
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
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 60px;
        }

        .header h1 {
            font-size: 3.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 20px;
            text-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .header p {
            font-size: 1.3rem;
            color: rgba(255,255,255,0.9);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .main-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 60px rgba(0,0,0,0.15);
        }

        .card-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            display: block;
        }

        .card h2 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        .card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
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
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        .features {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }

        .features h2 {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 40px;
            color: #333;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .feature {
            text-align: center;
            padding: 20px;
        }

        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            display: block;
        }

        .feature h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: #333;
        }

        .feature p {
            color: #666;
            line-height: 1.5;
        }

        .api-endpoints {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
        }

        .api-endpoints h3 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #333;
        }

        .endpoint {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            border-left: 4px solid #667eea;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }

        .endpoint.get { border-left-color: #28a745; }
        .endpoint.post { border-left-color: #007bff; }
        .endpoint.put { border-left-color: #ffc107; }
        .endpoint.delete { border-left-color: #dc3545; }

        .footer {
            text-align: center;
            color: rgba(255,255,255,0.8);
            margin-top: 60px;
        }

        .footer a {
            color: white;
            text-decoration: none;
            font-weight: 600;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2.5rem;
            }
            
            .header p {
                font-size: 1.1rem;
            }
            
            .card {
                padding: 30px;
            }
            
            .features {
                padding: 30px;
            }
        }
            </style>
    </head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Task Management API</h1>
            <p>Полнофункциональное REST API для управления задачами, построенное на Laravel с современным веб-интерфейсом</p>
        </div>

        <div class="main-content">
            <div class="card">
                <span class="card-icon">📋</span>
                <h2>Просмотр задач</h2>
                <p>Красивый веб-интерфейс для просмотра всех задач с фильтрацией по статусу и удобной навигацией.</p>
                <a href="/tasks" class="btn btn-primary">Открыть список задач</a>
            </div>

            <div class="card">
                <span class="card-icon">🧪</span>
                <h2>Тестирование API</h2>
                <p>Интерактивная страница для тестирования всех API endpoints с возможностью создания, редактирования и удаления задач.</p>
                <a href="/test_api.html" class="btn btn-secondary">Тестировать API</a>
            </div>

            <div class="card">
                <span class="card-icon">📚</span>
                <h2>Документация</h2>
                <p>Подробная документация по использованию API с примерами запросов и ответов для всех операций.</p>
                <a href="#api-docs" class="btn btn-secondary">Смотреть документацию</a>
            </div>
        </div>

        <div class="features">
            <h2>✨ Возможности системы</h2>
            <div class="features-grid">
                <div class="feature">
                    <span class="feature-icon">🔐</span>
                    <h3>Валидация данных</h3>
                    <p>Автоматическая проверка и валидация всех входящих данных с подробными сообщениями об ошибках</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">⚡</span>
                    <h3>REST API</h3>
                    <p>Полноценный REST API с поддержкой всех CRUD операций и правильными HTTP статусами</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">🎨</span>
                    <h3>Современный UI</h3>
                    <p>Красивый и адаптивный веб-интерфейс с анимациями и интуитивной навигацией</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">📊</span>
                    <h3>Статистика</h3>
                    <p>Отслеживание статусов задач и статистика по их выполнению</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">🔧</span>
                    <h3>Гибкость</h3>
                    <p>Легкое расширение функциональности и добавление новых возможностей</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">🛡️</span>
                    <h3>Надежность</h3>
                    <p>Обработка ошибок, логирование и стабильная работа системы</p>
                </div>
            </div>

            <div class="api-endpoints" id="api-docs">
                <h3>🔗 API Endpoints</h3>
                <div class="endpoint get">GET /api/tasks - Получить список всех задач</div>
                <div class="endpoint post">POST /api/tasks - Создать новую задачу</div>
                <div class="endpoint get">GET /api/tasks/{id} - Получить конкретную задачу</div>
                <div class="endpoint put">PUT /api/tasks/{id} - Обновить задачу</div>
                <div class="endpoint delete">DELETE /api/tasks/{id} - Удалить задачу</div>
            </div>
        </div>

        <div class="footer">
            <p>Построено с ❤️ на <a href="https://laravel.com" target="_blank">Laravel</a></p>
        </div>
    </div>
    </body>
</html>
