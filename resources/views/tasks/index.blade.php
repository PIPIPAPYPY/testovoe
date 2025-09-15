@extends('layouts.app')

@section('title', 'Список задач - Task Management')
@section('description', 'Управляйте своими задачами эффективно')

@section('content')
<style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg,#667eea 0%,#764ba2 100%);
            min-height:100vh;
            color:#333;
        }
        .container {
            max-width:1200px;
            margin:0 auto;
            padding:40px 20px;
            position:relative;
        }


        /* Заголовок задач в овале */
        .task-header {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }

        .task-header-badge {
            display: inline-flex;
            align-items: center;
            gap: 15px;
            background: white;
            padding: 8px 25px;
            border-radius: 50px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .task-header-badge h1,
        .task-header-badge .task-count {
            font-size: 2rem; /* одинаковый размер для текста и цифры */
            font-weight: 700;
            margin: 0;
            background: linear-gradient(135deg,#667eea,#764ba2); /* градиент для обоих */
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Фильтры */
        .filter-section {
            background:white;
            padding:20px;
            border-radius:20px;
            margin-top:20px;
            box-shadow:0 10px 20px rgba(0,0,0,0.08);
        }
        .filter-row { display:flex; gap:15px; flex-wrap:wrap; align-items:center; }
        .filter-group { display:flex; flex-direction:column; gap:5px; flex:1; }
        .filter-input { padding:10px 15px; border-radius:12px; border:1px solid #ced4da; box-shadow:0 4px 12px rgba(0,0,0,0.05); transition:all 0.2s; }
        .filter-input:focus { outline:0; border-color:#80bdff; box-shadow:0 0 0 0.2rem rgba(102,126,234,.25); }
        .filter-btn, .filter-btn-secondary { padding:10px 20px; border-radius:12px; font-weight:600; border:none; cursor:pointer; transition:all 0.3s; }
        .filter-btn { background:linear-gradient(135deg,#667eea,#764ba2); color:white; box-shadow:0 6px 18px rgba(102,126,234,0.3); }
        .filter-btn:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(102,126,234,0.4); }
        .filter-btn-secondary { background:#e9ecef; color:#495057; box-shadow:0 4px 12px rgba(0,0,0,0.1); text-decoration:none; display:inline-flex; align-items:center; justify-content:center; }
        .filter-btn-secondary:hover { background:#d4d9e2; }

        /* Сетка задач */
        .task-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(350px,1fr)); gap:20px; margin-top:20px; }
        .task-card { background:white; border-radius:20px; padding:25px; box-shadow:0 20px 40px rgba(0,0,0,0.1); transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .task-card:hover { transform:translateY(-5px); box-shadow:0 30px 60px rgba(0,0,0,0.15); }
        .task-title { font-size:18px; font-weight:bold; margin-bottom:10px; }
        .task-description { color:#666; margin-bottom:15px; line-height:1.5; }
        .task-status { display:inline-block; padding:5px 12px; border-radius:20px; font-size:12px; font-weight:bold; text-transform:uppercase; }
        .status-todo { background-color:#fff3cd; color:#856404; }
        .status-in_progress { background-color:#d1ecf1; color:#0c5460; }
        .status-done { background-color:#d4edda; color:#155724; }
        
        /* Оптимизация производительности */
        .task-grid { will-change: transform; }
        .task-card { will-change: transform; }
        .filter-section { will-change: auto; }
        
        /* Анимации только для поддерживающих устройств */
        @media (prefers-reduced-motion: no-preference) {
            .task-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
            .task-card:hover { transform: translateY(-5px); }
        }
        
        /* Оптимизация для мобильных */
        @media (max-width: 768px) {
            .task-grid { grid-template-columns: 1fr; gap: 15px; }
            .filter-row { flex-direction: column; }
            .filter-group { flex: none; }
        }
    </style>
    
    <div class="container">


    <!-- Заголовок списка -->
    <div class="task-header">
        <div class="task-header-badge">
            <h1>Список задач</h1>
            <span class="task-count">{{ count($tasks) }}</span>
        </div>
    </div>

    <!-- Фильтр -->
    <div class="filter-section">
        <h3 style="margin-bottom:20px;">🔍 Фильтр задач</h3>
        <form method="GET" action="{{ route('tasks.index') }}">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="status">Статус:</label>
                    <select name="status" id="status" class="filter-input">
                        <option value="">Все задачи</option>
                        <option value="todo" {{ request('status')=='todo'?'selected':'' }}>К выполнению</option>
                        <option value="in_progress" {{ request('status')=='in_progress'?'selected':'' }}>В работе</option>
                        <option value="done" {{ request('status')=='done'?'selected':'' }}>Выполнено</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="search">Поиск:</label>
                    <input type="text" name="search" id="search" class="filter-input" placeholder="Название или описание..." value="{{ request('search') }}">
                </div>
                <div class="filter-group" style="display:flex; gap:10px; align-items:flex-end;">
                    <button type="submit" class="filter-btn">Применить</button>
                    <a href="{{ route('tasks.index') }}" class="filter-btn-secondary">Сбросить</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Список задач -->
    @if(count($tasks) > 0)
        <div class="task-grid">
            @foreach($tasks as $task)
                @php
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
                    <span class="task-status {{ $statusClass }}">{{ $task->status }}</span>
                </div>
            @endforeach
        </div>
    @else
        <p style="text-align:center; color:#fff; font-style:italic;">Задачи не найдены.</p>
    @endif

    </div>
@endsection

@section('scripts')
<script>
    // Оптимизированный JavaScript для страницы задач
    document.addEventListener('DOMContentLoaded', function() {
        // Виртуализация для больших списков (если нужно)
        const taskGrid = document.querySelector('.task-grid');
        if (taskGrid && taskGrid.children.length > 50) {
            // Здесь можно добавить виртуализацию
            console.log('Большой список задач, рекомендуется виртуализация');
        }
        
        // Предзагрузка следующих страниц пагинации
        const paginationLinks = document.querySelectorAll('.pagination a');
        paginationLinks.forEach(link => {
            link.addEventListener('mouseenter', function() {
                const href = this.getAttribute('href');
                if (href && !document.querySelector(`link[href="${href}"]`)) {
                    const prefetchLink = document.createElement('link');
                    prefetchLink.rel = 'prefetch';
                    prefetchLink.href = href;
                    document.head.appendChild(prefetchLink);
                }
            });
        });
        
        // Оптимизация фильтров
        const filterForm = document.querySelector('form[method="GET"]');
        if (filterForm) {
            const filterInputs = filterForm.querySelectorAll('input, select');
            let filterTimeout;
            
            filterInputs.forEach(input => {
                input.addEventListener('change', function() {
                    clearTimeout(filterTimeout);
                    filterTimeout = setTimeout(() => {
                        filterForm.submit();
                    }, 500);
                });
            });
        }
    });
</script>
@endsection
