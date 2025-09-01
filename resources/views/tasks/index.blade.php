@extends('layouts.app')

@section('title', 'Управление задачами')

@section('content')
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
    </style>

    <h1>📋 Управление задачами</h1>

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

    <h2>📝 Список задач ({{ $tasks->total() }})</h2>

    @if($tasks->count() > 0)
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

    <div style="margin-top: 20px;">{{ $tasks->withQueryString()->links() }}</div>

    <div style="margin-top: 30px; text-align: center;">
        <p style="color: #666;">
            💡 Для тестирования API используйте <a href="/test_api.html" style="color: #007bff;">тестовую страницу</a>
        </p>
    </div>
@endsection
