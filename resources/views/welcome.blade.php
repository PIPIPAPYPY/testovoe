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
            position: relative;
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

        .auth-buttons {
            position: absolute;
            top: 0;
            right: 0;
            display: flex;
            gap: 10px;
        }

        .auth-btn {
            background: white;
            color: #667eea;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .auth-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            background: #f8f9fa;
        }

        .logout-btn {
            background: #dc3545 !important;
            color: white !important;
        }

        .logout-btn:hover {
            background: #c82333 !important;
        }

        .user-name {
            color: white;
            font-weight: 600;
            margin-right: 10px;
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
            <div class="auth-buttons" id="authButtons">
                <a href="/login" class="auth-btn">Войти</a>
                <a href="/register" class="auth-btn">Регистрация</a>
            </div>
            <div class="user-info" id="userInfo" style="display: none;">
                <span class="user-name" id="userName">Загрузка...</span>
                <a href="/tasks" class="auth-btn">Мои задачи</a>
                <button onclick="logout()" class="auth-btn logout-btn">Выйти</button>
            </div>
            <h1>🚀 Task Management API</h1>
            <p>Полнофункциональное REST API для управления задачами, построенное на Laravel с современным веб-интерфейсом</p>
        </div>

        <div class="main-content">
            <div class="card">
                <span class="card-icon">📋</span>
                <h2>Просмотр задач</h2>
                <p>Красивый веб-интерфейс для просмотра всех задач с фильтрацией по статусу, приоритету и дедлайну.</p>
                <a href="/tasks" class="btn btn-primary">Открыть список задач</a>
            </div>

            <div class="card">
                <span class="card-icon">🧪</span>
                <h2>Тестирование API</h2>
                <p>Интерактивная страница для тестирования всех API endpoints с возможностью создания, редактирования и удаления задач.</p>
                <a href="/api-test" class="btn btn-primary">Тестировать API</a>
            </div>

            <div class="card">
                <span class="card-icon">📊</span>
                <h2>Аналитика задач</h2>
                <p>Детальная аналитика с интерактивными графиками, статистикой выполнения и трендами продуктивности.</p>
                <a href="/analytics" class="btn btn-primary">Открыть аналитику</a>
            </div>
        </div>

        <div class="features">
            <h2>✨ Возможности системы</h2>
            <div class="features-grid">
                <div class="feature">
                    <span class="feature-icon">🔐</span>
                    <h3>Аутентификация</h3>
                    <p>Безопасная регистрация и авторизация пользователей с токенами Laravel Sanctum</p>
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
                    <h3>Фильтрация и сортировка</h3>
                    <p>Продвинутая фильтрация по статусу, приоритету, дедлайну и сортировка задач</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">⏰</span>
                    <h3>Дедлайны и приоритеты</h3>
                    <p>Управление дедлайнами задач и система приоритетов для эффективного планирования</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">🛡️</span>
                    <h3>Надежность</h3>
                    <p>Обработка ошибок, логирование и стабильная работа системы</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">🚀</span>
                    <h3>Высокая производительность</h3>
                    <p>Оптимизированные запросы, кэширование и составные индексы для быстрой работы</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">🔒</span>
                    <h3>Продвинутая безопасность</h3>
                    <p>Sanctum токены, CSRF защита, rate limiting и валидация данных</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">🐳</span>
                    <h3>Docker контейнеризация</h3>
                    <p>Готовая Docker среда с PostgreSQL, Nginx и PHP-FPM для легкого развертывания</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">💾</span>
                    <h3>Умное кэширование</h3>
                    <p>Автоматическое кэширование статистики и очистка при изменениях данных</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">🔍</span>
                    <h3>Продвинутый поиск</h3>
                    <p>Полнотекстовый поиск по задачам с оптимизированными запросами и индексами</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">📈</span>
                    <h3>Статистика в реальном времени</h3>
                    <p>Мгновенная статистика по статусам задач с кэшированием результатов</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">⚡</span>
                    <h3>Сжатие контента</h3>
                    <p>Автоматическое Gzip сжатие для уменьшения трафика и ускорения загрузки</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">🛠️</span>
                    <h3>Оптимизированная архитектура</h3>
                    <p>Middleware сжатия, оптимизированные запросы и современные практики Laravel</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">📊</span>
                    <h3>Аналитика и отчеты</h3>
                    <p>Детальная аналитика задач с графиками, статистикой выполнения и трендами продуктивности</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">📈</span>
                    <h3>Визуализация данных</h3>
                    <p>Интерактивные графики с Chart.js для анализа продуктивности и паттернов работы</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">🎯</span>
                    <h3>Умная пагинация</h3>
                    <p>Красивая и компактная пагинация с оптимизацией для больших объемов данных</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">✨</span>
                    <h3>Модальные окна</h3>
                    <p>Современные модальные окна для создания и редактирования задач с валидацией</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">🔄</span>
                    <h3>Быстрое изменение статуса</h3>
                    <p>Мгновенное изменение статуса задач с hover-эффектами и анимациями</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">🎨</span>
                    <h3>Адаптивный дизайн</h3>
                    <p>Полностью адаптивный интерфейс, оптимизированный для всех устройств и экранов</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">⚡</span>
                    <h3>Автоматическое обновление</h3>
                    <p>Автоматическое обновление статистики и данных после изменений без перезагрузки</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">🔔</span>
                    <h3>Уведомления</h3>
                    <p>Красивые уведомления об успешных операциях и ошибках с анимациями</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">🗂️</span>
                    <h3>Сидеры данных</h3>
                    <p>Готовые сидеры для генерации тестовых данных и аналитики</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">🔧</span>
                    <h3>Миграции БД</h3>
                    <p>Структурированные миграции с индексами для оптимальной производительности</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">🎪</span>
                    <h3>Интерактивные элементы</h3>
                    <p>Hover-эффекты, анимации и интерактивные элементы для лучшего UX</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">🧪</span>
                    <h3>Комплексное тестирование</h3>
                    <p>Полное покрытие тестами: Unit, Feature, Integration, Performance и Security тесты</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">⚡</span>
                    <h3>Тесты производительности</h3>
                    <p>Автоматическое тестирование производительности с нагрузочными тестами и оптимизацией</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">🔒</span>
                    <h3>Тесты безопасности</h3>
                    <p>RBAC, токен-безопасность, авторизация и защита от уязвимостей</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">🌐</span>
                    <h3>Интеграционные тесты</h3>
                    <p>Тестирование внешних API, уведомлений и аналитических сервисов</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">🐘</span>
                    <h3>PostgreSQL совместимость</h3>
                    <p>Полная поддержка PostgreSQL с тестированием совместимости и производительности</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">🛠️</span>
                    <h3>Обработка ошибок</h3>
                    <p>Тестирование edge cases, валидации и обработки исключительных ситуаций</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">📊</span>
                    <h3>HTTP мокирование</h3>
                    <p>VCR-подобные фикстуры для тестирования внешних сервисов без реальных запросов</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">🔄</span>
                    <h3>Жизненный цикл задач</h3>
                    <p>End-to-end тестирование полного жизненного цикла от создания до удаления</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">📈</span>
                    <h3>Аналитические тесты</h3>
                    <p>Тестирование аналитических сервисов с множественными провайдерами</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">🎯</span>
                    <h3>Тестовые данные</h3>
                    <p>Автоматическая генерация тестовых данных с сидерами и фабриками</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">⚙️</span>
                    <h3>CI/CD готовность</h3>
                    <p>Готовность к непрерывной интеграции с автоматизированным тестированием</p>
                </div>
            </div>

            <div class="api-endpoints" id="api-docs">
                <h3>🔗 API Endpoints</h3>

                <h4 style="margin: 20px 0 10px 0; color: #667eea; font-size: 1.2rem;">🔐 Аутентификация</h4>
                <div class="endpoint post">POST /api/register - Регистрация пользователя</div>
                <div class="endpoint post">POST /api/login - Авторизация пользователя</div>
                <div class="endpoint post">POST /api/auth/register - Регистрация пользователя</div>
                <div class="endpoint post">POST /api/auth/login - Авторизация пользователя</div>
                <div class="endpoint post">POST /api/auth/logout - Выход пользователя (требует токен)</div>
                <div class="endpoint get">GET /api/user - Получить данные текущего пользователя</div>

                <h4 style="margin: 20px 0 10px 0; color: #667eea; font-size: 1.2rem;">📋 Управление задачами</h4>
                <div class="endpoint get">GET /api/tasks - Получить список задач (с фильтрацией и пагинацией)</div>
                <div class="endpoint post">POST /api/tasks - Создать новую задачу</div>
                <div class="endpoint get">GET /api/tasks/{task} - Получить конкретную задачу</div>
                <div class="endpoint put">PUT /api/tasks/{task} - Обновить задачу</div>
                <div class="endpoint delete">DELETE /api/tasks/{task} - Удалить задачу</div>

                <h4 style="margin: 20px 0 10px 0; color: #667eea; font-size: 1.2rem;">📊 Аналитика</h4>
                <div class="endpoint get">GET /api/analytics/completed-tasks-chart - График выполненных задач</div>
                <div class="endpoint get">GET /api/analytics/category-chart - График по категориям</div>
                <div class="endpoint get">GET /api/analytics/tag-chart - График по тегам</div>
                <div class="endpoint get">GET /api/analytics/productive-days-chart - График продуктивных дней</div>
                <div class="endpoint get">GET /api/analytics/overall-stats - Общая статистика пользователя</div>
                <div class="endpoint get">GET /api/analytics/categories - Доступные категории</div>
                <div class="endpoint get">GET /api/analytics/tags - Доступные теги</div>
            </div>

        </div>

        <div class="footer">
            <p>Построено с ❤️ на <a href="https://laravel.com" target="_blank">Laravel</a></p>
        </div>
    </div>

    <script>
        window.onload = function() {
            checkAuth();
        };

        function getToken() {
            return localStorage.getItem('token') || '';
        }

        function authHeaders() {
            const token = getToken();
            return token ? { 'Authorization': `Bearer ${token}` } : {};
        }

        async function checkAuth() {
            const token = getToken();

            if (!token) {
                showAuthButtons();
                return;
            }

            try {
                const response = await fetch('/api/user', {
                    headers: {
                        'Accept': 'application/json',
                        ...authHeaders(),
                    }
                });

                if (response.ok) {
                    const user = await response.json();
                    showUserInfo(user.name);
                } else {
                    localStorage.removeItem('token');
                    showAuthButtons();
                }
            } catch (error) {
                localStorage.removeItem('token');
                showAuthButtons();
            }
        }

        function showAuthButtons() {
            document.getElementById('authButtons').style.display = 'flex';
            document.getElementById('userInfo').style.display = 'none';
        }

        function showUserInfo(userName) {
            document.getElementById('userName').textContent = userName;
            document.getElementById('authButtons').style.display = 'none';
            document.getElementById('userInfo').style.display = 'flex';
        }

        async function logout() {
            try {
                await fetch('/api/logout', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        ...authHeaders(),
                    }
                });
            } catch (error) {
                console.error('Ошибка при выходе:', error);
            }

            localStorage.removeItem('token');
            showAuthButtons();
        }
    </script>
</body>
</html>
