<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление задачами</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .task-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .task-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .task-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        .task-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .task-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        .task-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* Исправленные классы для статусов */
        .status-todo {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-in_progress {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .status-done {
            background-color: #d4edda;
            color: #155724;
        }

        .task-meta {
            margin-top: 15px;
            font-size: 12px;
            color: #999;
        }
        .api-info {
            background: #e9ecef;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .api-info h3 {
            margin-top: 0;
            color: #495057;
        }
        .api-endpoints {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        .endpoint {
            background: white;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
        }
        .method-get { border-left: 4px solid #28a745; }
        .method-post { border-left: 4px solid #007bff; }
        .method-put { border-left: 4px solid #ffc107; }
        .method-delete { border-left: 4px solid #dc3545; }
        
        /* Стили для фильтра */
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
        }
        .filter-row {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .filter-group label {
            font-weight: 600;
            color: #495057;
            font-size: 14px;
        }
        .filter-input {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .filter-input:focus {
            outline: 0;
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .filter-select {
            min-width: 150px;
        }
        .filter-search {
            min-width: 250px;
        }
        .filter-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.15s ease-in-out;
        }
        .filter-btn:hover {
            background: #0056b3;
        }
        .filter-btn-secondary {
            background: #6c757d;
        }
        .filter-btn-secondary:hover {
            background: #545b62;
        }
        .status-badges {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            color: inherit;
        }
        .status-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status-badge.active {
            box-shadow: 0 0 0 2px #007bff;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>📋 Управление задачами</h1>

    <!-- Фильтр задач -->
    <div class="filter-section">
        <h3 style="margin-top: 0; margin-bottom: 15px; color: #495057;">🔍 Фильтр задач</h3>
        
        <form method="GET" action="{{ route('tasks.index') }}">
            <!-- DEBUG: Форма отправляется на: {{ route('tasks.index') }} -->
            <div class="filter-row">
                <div class="filter-group">
                    <label for="status">Статус:</label>
                    <select name="status" id="status" class="filter-input filter-select">
                        <option value="">Все задачи</option>
                        <option value="todo" {{ request('status') == 'todo' ? 'selected' : '' }}>К выполнению</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>В работе</option>
                        <option value="done" {{ request('status') == 'done' ? 'selected' : '' }}>Выполнено</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="search">Поиск:</label>
                    <input type="text" name="search" id="search" class="filter-input filter-search" 
                           placeholder="Поиск по названию или описанию..." 
                           value="{{ request('search') }}">
                </div>
                
                <div class="filter-group" style="margin-top: 20px;">
                    <button type="submit" class="filter-btn">Применить фильтр</button>
                    <a href="{{ route('tasks.index') }}" class="filter-btn filter-btn-secondary" style="text-decoration: none; margin-left: 5px;">Сбросить</a>
                </div>
            </div>
        </form>
        
        <!-- Быстрые фильтры -->
        <div class="status-badges">
            <span style="color: #6c757d; font-size: 12px; margin-right: 10px;">Быстрые фильтры:</span>
            <a href="{{ route('tasks.index') }}" class="status-badge {{ !request('status') ? 'active' : '' }}" 
               style="background-color: #e9ecef; color: #495057;">
                Все ({{ $statusCounts['all'] }})
            </a>
            <a href="{{ route('tasks.index', ['status' => 'todo']) }}" class="status-badge {{ request('status') == 'todo' ? 'active' : '' }} status-todo">
                К выполнению ({{ $statusCounts['todo'] }})
            </a>
            <a href="{{ route('tasks.index', ['status' => 'in_progress']) }}" class="status-badge {{ request('status') == 'in_progress' ? 'active' : '' }} status-in_progress">
                В работе ({{ $statusCounts['in_progress'] }})
            </a>
            <a href="{{ route('tasks.index', ['status' => 'done']) }}" class="status-badge {{ request('status') == 'done' ? 'active' : '' }} status-done">
                Выполнено ({{ $statusCounts['done'] }})
            </a>
        </div>
    </div>

    <div class="api-info">
        <h3>🔗 API Endpoints</h3>
        <div class="api-endpoints">
            <div class="endpoint method-get">GET /api/tasks - Получить все задачи</div>
            <div class="endpoint method-post">POST /api/tasks - Создать задачу</div>
            <div class="endpoint method-get">GET /api/tasks/{id} - Получить задачу</div>
            <div class="endpoint method-put">PUT /api/tasks/{id} - Обновить задачу</div>
            <div class="endpoint method-delete">DELETE /api/tasks/{id} - Удалить задачу</div>
        </div>
    </div>

    <h2>📝 Список задач ({{ count($tasks) }})</h2>
    
    <div style="background: #f8f9fa; padding: 10px; border-radius: 6px; margin-bottom: 20px; font-size: 12px; color: #6c757d;">
        <strong>🔍 DEBUG:</strong> 
        Текущий URL: {{ url()->current() }} | 
        Маршрут tasks.index: {{ route('tasks.index') }} | 
        Параметры: {{ json_encode(request()->all()) }}
    </div>
    
    @if(request('status') || request('search'))
        <div style="background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 6px; margin-bottom: 20px; font-size: 14px;">
            <strong>🔍 Активные фильтры:</strong>
            @if(request('status'))
                <span style="background: white; padding: 2px 8px; border-radius: 12px; margin-left: 5px;">
                    Статус: {{ match(request('status')) { 'todo' => 'К выполнению', 'in_progress' => 'В работе', 'done' => 'Выполнено', default => request('status') } }}
                </span>
            @endif
            @if(request('search'))
                <span style="background: white; padding: 2px 8px; border-radius: 12px; margin-left: 5px;">
                    Поиск: "{{ request('search') }}"
                </span>
            @endif
        </div>
    @endif

    @if(count($tasks) > 0)
        <div class="task-grid">
            @foreach($tasks as $task)
                @php
                    // Преобразуем статус задачи в класс для CSS
                    $statusClass = match($task->status) {
                        'todo' => 'status-todo',
                        'in_progress' => 'status-in_progress',
                        'done' => 'status-done',
                        default => 'status-todo',
                    };
                @endphp

                <div class="task-card">
                    <div class="task-title">{{ $task->title }}</div>
                    <div class="task-description">{{ $task->description }}</div>
                    <span class="task-status {{ $statusClass }}">
                            {{ $task->status }}
                        </span>
                </div>
            @endforeach
        </div>
    @else
        <p style="text-align: center; color: #666; font-style: italic;">
            Задачи не найдены. Создайте первую задачу через API!
        </p>
    @endif

    <div style="margin-top: 30px; text-align: center;">
        <p style="color: #666;">
            💡 Для тестирования API используйте <a href="/test_api.html" style="color: #007bff;">тестовую страницу</a>
        </p>
    </div>
</div>
</body>
</html>
