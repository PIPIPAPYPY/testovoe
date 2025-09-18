<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тестирование API - Task Management</title>
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        margin: 0;
        padding: 20px;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        background: white;
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    }

    .header {
        text-align: center;
        margin-bottom: 40px;
    }

    .header h1 {
        font-size: 2.5rem;
        color: #333;
        margin-bottom: 10px;
    }

    .header p {
        color: #666;
        font-size: 1.1rem;
    }

    .auth-section {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
    }

    .auth-section h2 {
        color: #333;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #333;
    }

    .form-group input {
        width: 100%;
        padding: 12px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
    }

    .form-group input:focus {
        outline: none;
        border-color: #667eea;
    }

    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-right: 10px;
        margin-bottom: 10px;
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
        background: #6c757d;
        color: white;
    }

    .btn-success {
        background: #28a745;
        color: white;
    }

    .btn-danger {
        background: #dc3545;
        color: white;
    }

    .btn-warning {
        background: #ffc107;
        color: #333;
    }

    .api-section {
        margin-bottom: 30px;
    }

    .api-section h2 {
        color: #333;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e5e7eb;
    }

    .endpoint-group {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .endpoint-title {
        font-weight: 600;
        color: #333;
        margin-bottom: 15px;
        font-size: 1.1rem;
    }

    .response-area {
        background: #1e1e1e;
        color: #d4d4d4;
        padding: 20px;
        border-radius: 8px;
        font-family: 'Courier New', monospace;
        font-size: 0.9rem;
        margin-top: 15px;
        min-height: 100px;
        white-space: pre-wrap;
        overflow-x: auto;
    }

    .status-success {
        color: #28a745;
    }

    .status-error {
        color: #dc3545;
    }

    .token-display {
        background: #e9ecef;
        padding: 10px;
        border-radius: 5px;
        font-family: 'Courier New', monospace;
        font-size: 0.9rem;
        word-break: break-all;
        margin-top: 10px;
    }

    .hidden {
        display: none;
    }

    .user-info {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .back-btn {
        display: inline-block;
        padding: 12px 24px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        text-decoration: none;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
        margin-bottom: 30px;
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    }
    
    .back-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        color: white;
        text-decoration: none;
    }
    </style>
</head>
<body>
<div class="container">
    <a href="/" class="back-btn">← Назад на главную</a>
    <div class="header">
        <h1>🧪 Тестирование API</h1>
        <p>Интерактивная страница для тестирования всех API endpoints</p>
    </div>

    <!-- Секция аутентификации -->
    <div class="auth-section">
        <h2>🔐 Аутентификация</h2>
        
        <div id="loginForm">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" placeholder="user@example.com">
            </div>
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" placeholder="Пароль">
            </div>
            <button class="btn btn-primary" onclick="login()">Войти</button>
            <button class="btn btn-secondary" onclick="register()">Регистрация</button>
        </div>

        <div id="userInfo" class="user-info hidden">
            <strong>Вы вошли как:</strong> <span id="userName"></span>
            <div class="token-display">
                <strong>Токен:</strong> <span id="userToken"></span>
            </div>
            <button class="btn btn-danger" onclick="logout()">Выйти</button>
        </div>
    </div>

    <!-- Секция задач -->
    <div class="api-section">
        <h2>📋 Управление задачами</h2>
        
        <div class="endpoint-group">
            <div class="endpoint-title">GET /api/tasks - Получить все задачи</div>
            <button class="btn btn-primary" onclick="getTasks()">Получить задачи</button>
            <div class="response-area" id="getTasksResponse"></div>
        </div>

        <div class="endpoint-group">
            <div class="endpoint-title">POST /api/tasks - Создать новую задачу</div>
            <div class="form-group">
                <label for="taskTitle">Название задачи:</label>
                <input type="text" id="taskTitle" placeholder="Введите название задачи">
            </div>
            <div class="form-group">
                <label for="taskDescription">Описание:</label>
                <input type="text" id="taskDescription" placeholder="Введите описание">
            </div>
            <div class="form-group">
                <label for="taskStatus">Статус:</label>
                <select id="taskStatus" style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px;">
                    <option value="todo" selected>К выполнению</option>
                    <option value="in_progress">В работе</option>
                    <option value="done">Выполнено</option>
                </select>
            </div>
            <div class="form-group">
                <label for="taskPriority">Приоритет:</label>
                <select id="taskPriority" style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px;">
                    <option value="1">Низкий</option>
                    <option value="2">Ниже среднего</option>
                    <option value="3" selected>Средний</option>
                    <option value="4">Выше среднего</option>
                    <option value="5">Высокий</option>
                </select>
            </div>
            <div class="form-group">
                <label for="taskDeadline">Дедлайн:</label>
                <input type="datetime-local" id="taskDeadline">
            </div>
            <button class="btn btn-success" onclick="createTask()">Создать задачу</button>
            <div class="response-area" id="createTaskResponse"></div>
        </div>

        <div class="endpoint-group">
            <div class="endpoint-title">GET /api/tasks/{id} - Получить конкретную задачу</div>
            <div class="form-group">
                <label for="taskId">ID задачи:</label>
                <input type="number" id="taskId" placeholder="Введите ID задачи">
            </div>
            <button class="btn btn-primary" onclick="getTask()">Получить задачу</button>
            <div class="response-area" id="getTaskResponse"></div>
        </div>

        <div class="endpoint-group">
            <div class="endpoint-title">PUT /api/tasks/{id} - Обновить задачу</div>
            <div class="form-group">
                <label for="updateTaskId">ID задачи:</label>
                <input type="number" id="updateTaskId" placeholder="Введите ID задачи">
            </div>
            <div class="form-group">
                <label for="updateTaskTitle">Название:</label>
                <input type="text" id="updateTaskTitle" placeholder="Новое название">
            </div>
            <div class="form-group">
                <label for="updateTaskStatus">Статус:</label>
                <select id="updateTaskStatus" style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px;">
                    <option value="todo">К выполнению</option>
                    <option value="in_progress">В работе</option>
                    <option value="done">Выполнено</option>
                </select>
            </div>
            <button class="btn btn-warning" onclick="updateTask()">Обновить задачу</button>
            <div class="response-area" id="updateTaskResponse"></div>
        </div>

        <div class="endpoint-group">
            <div class="endpoint-title">DELETE /api/tasks/{id} - Удалить задачу</div>
            <div class="form-group">
                <label for="deleteTaskId">ID задачи:</label>
                <input type="number" id="deleteTaskId" placeholder="Введите ID задачи">
            </div>
            <button class="btn btn-danger" onclick="deleteTask()">Удалить задачу</button>
            <div class="response-area" id="deleteTaskResponse"></div>
        </div>
    </div>
</div>

<script>
    let authToken = localStorage.getItem('token') || '';

    window.onload = function() {
        if (authToken) {
            checkAuth();
        }
    };

    function getAuthHeaders() {
        return authToken ? { 'Authorization': `Bearer ${authToken}` } : {};
    }

    async function checkAuth() {
        try {
            const response = await fetch('/api/user', {
                headers: {
                    'Accept': 'application/json',
                    ...getAuthHeaders(),
                }
            });

            if (response.ok) {
                const user = await response.json();
                showUserInfo(user.name);
            } else {
                logout();
            }
        } catch (error) {
            logout();
        }
    }

    function showUserInfo(userName) {
        document.getElementById('loginForm').classList.add('hidden');
        document.getElementById('userInfo').classList.remove('hidden');
        document.getElementById('userName').textContent = userName;
        document.getElementById('userToken').textContent = authToken;
    }

    function hideUserInfo() {
        document.getElementById('loginForm').classList.remove('hidden');
        document.getElementById('userInfo').classList.add('hidden');
    }

    async function login() {
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        if (!email || !password) {
            alert('Заполните все поля');
            return;
        }

        try {
            const response = await fetch('/api/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ email, password })
            });

            const data = await response.json();
            
            if (response.ok) {
                authToken = data.token;
                localStorage.setItem('token', authToken);
                showUserInfo(data.user.name);
                alert('Успешный вход!');
            } else {
                alert('Ошибка: ' + (data.message || 'Неверные данные'));
            }
        } catch (error) {
            alert('Ошибка сети: ' + error.message);
        }
    }

    async function register() {
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        if (!email || !password) {
            alert('Заполните все поля');
            return;
        }

        try {
            const response = await fetch('/api/auth/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ 
                    name: email.split('@')[0], 
                    email, 
                    password,
                    password_confirmation: password 
                })
            });

            const data = await response.json();
            
            if (response.ok) {
                authToken = data.token;
                localStorage.setItem('token', authToken);
                showUserInfo(data.user.name);
                alert('Регистрация успешна!');
            } else {
                alert('Ошибка: ' + (data.message || 'Ошибка регистрации'));
            }
        } catch (error) {
            alert('Ошибка сети: ' + error.message);
        }
    }

    async function logout() {
        try {
            await fetch('/api/auth/logout', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    ...getAuthHeaders(),
                }
            });
        } catch (error) {
            console.error('Ошибка при выходе:', error);
        }
        
        authToken = '';
        localStorage.removeItem('token');
        hideUserInfo();
    }

    async function getTasks() {
        try {
            const response = await fetch('/api/tasks', {
                headers: {
                    'Accept': 'application/json',
                    ...getAuthHeaders(),
                }
            });

            const data = await response.json();
            displayResponse('getTasksResponse', response.status, data);
        } catch (error) {
            displayResponse('getTasksResponse', 0, { error: error.message });
        }
    }

    async function createTask() {
        const title = document.getElementById('taskTitle').value;
        const description = document.getElementById('taskDescription').value;
        const status = document.getElementById('taskStatus').value;
        const priority = document.getElementById('taskPriority').value;
        const deadline = document.getElementById('taskDeadline').value;

        if (!title) {
            alert('Введите название задачи');
            return;
        }

        const taskData = { title, description, status, priority };
        if (deadline) {
            taskData.deadline = deadline;
        }

        try {
            const response = await fetch('/api/tasks', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...getAuthHeaders(),
                },
                body: JSON.stringify(taskData)
            });

            const data = await response.json();
            displayResponse('createTaskResponse', response.status, data);
        } catch (error) {
            displayResponse('createTaskResponse', 0, { error: error.message });
        }
    }

    async function getTask() {
        const id = document.getElementById('taskId').value;
        if (!id) {
            alert('Введите ID задачи');
            return;
        }

        try {
            const response = await fetch(`/api/tasks/${id}`, {
                headers: {
                    'Accept': 'application/json',
                    ...getAuthHeaders(),
                }
            });

            const data = await response.json();
            displayResponse('getTaskResponse', response.status, data);
        } catch (error) {
            displayResponse('getTaskResponse', 0, { error: error.message });
        }
    }

    async function updateTask() {
        const id = document.getElementById('updateTaskId').value;
        const title = document.getElementById('updateTaskTitle').value;
        const status = document.getElementById('updateTaskStatus').value;

        if (!id) {
            alert('Введите ID задачи');
            return;
        }

        const updateData = {};
        if (title) updateData.title = title;
        if (status) updateData.status = status;

        try {
            const response = await fetch(`/api/tasks/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...getAuthHeaders(),
                },
                body: JSON.stringify(updateData)
            });

            const data = await response.json();
            displayResponse('updateTaskResponse', response.status, data);
        } catch (error) {
            displayResponse('updateTaskResponse', 0, { error: error.message });
        }
    }

    async function deleteTask() {
        const id = document.getElementById('deleteTaskId').value;
        if (!id) {
            alert('Введите ID задачи');
            return;
        }

        try {
            const response = await fetch(`/api/tasks/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    ...getAuthHeaders(),
                }
            });

            const data = await response.json();
            displayResponse('deleteTaskResponse', response.status, data);
        } catch (error) {
            displayResponse('deleteTaskResponse', 0, { error: error.message });
        }
    }

    function displayResponse(elementId, status, data) {
        const element = document.getElementById(elementId);
        const statusClass = status >= 200 && status < 300 ? 'status-success' : 'status-error';
        element.innerHTML = `<span class="${statusClass}">Status: ${status}</span>\n\n${JSON.stringify(data, null, 2)}`;
    }
</script>
</body>
</html>
