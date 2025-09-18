@extends('layouts.app')

@section('title', 'Список задач - Task Management')
@section('description', 'Управляйте своими задачами эффективно')

@section('content')
<style>
        /* Переопределяем стили body для соответствия главной странице */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main.container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* Заголовок в стиле главной страницы */
        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 3rem;
            font-weight: 700;
            color: white;
            margin-bottom: 15px;
            text-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .page-header .subtitle {
            font-size: 1.2rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 20px;
        }

        .task-stats {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .stat-badge {
            background: white;
            padding: 10px 20px;
            border-radius: 50px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            font-weight: 600;
            color: #333;
            transition: transform 0.3s ease;
        }

        .stat-badge:hover {
            transform: translateY(-2px);
        }

        .stat-number {
            color: #667eea;
            font-size: 1.2rem;
            font-weight: 700;
        }

        /* Фильтры в стиле карточек главной страницы */
        .filter-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .filter-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .filter-section h3 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-row { 
            display: flex; 
            gap: 20px; 
            flex-wrap: wrap; 
            align-items: end; 
        }

        .filter-group { 
            display: flex; 
            flex-direction: column; 
            gap: 8px; 
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }

        .filter-input { 
            padding: 12px 16px; 
            border-radius: 12px; 
            border: 2px solid #e9ecef; 
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .filter-input:focus { 
            outline: 0; 
            border-color: #667eea; 
            box-shadow: 0 0 0 0.2rem rgba(102,126,234,.25);
            background: white;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            align-items: end;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 12px rgba(102,126,234,0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102,126,234,0.4);
        }

        .btn-secondary {
            background: #e9ecef;
            color: #495057;
            border: 2px solid #e9ecef;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
        }

        /* Сетка задач в стиле главной страницы */
        .task-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); 
            gap: 30px; 
            margin-top: 30px; 
        }

        .task-card { 
            background: white; 
            border-radius: 20px; 
            padding: 30px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1); 
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .task-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .task-card:hover { 
            transform: translateY(-10px); 
            box-shadow: 0 30px 60px rgba(0,0,0,0.15); 
        }

        .task-title { 
            font-size: 1.4rem; 
            font-weight: 600; 
            margin-bottom: 15px; 
            color: #333;
        }

        .task-description { 
            color: #666; 
            margin-bottom: 20px; 
            line-height: 1.6; 
        }

        .task-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .task-status { 
            display: inline-flex;
            align-items: center;
            padding: 8px 16px; 
            border-radius: 50px; 
            font-size: 0.8rem; 
            font-weight: 600; 
            letter-spacing: 0.3px;
            white-space: nowrap;
            line-height: 1;
        }

        .status-todo { 
            background: linear-gradient(135deg, #ffeaa7, #fdcb6e); 
            color: #2d3436; 
        }

        .status-in_progress { 
            background: linear-gradient(135deg, #74b9ff, #0984e3); 
            color: white; 
        }

        .status-done { 
            background: linear-gradient(135deg, #00b894, #00a085); 
            color: white; 
        }

        .task-date {
            font-size: 0.9rem;
            color: #999;
        }

        .task-status-section {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-actions {
            display: flex;
            gap: 5px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .task-card:hover .status-actions {
            opacity: 1;
        }

        .status-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 6px;
            transition: all 0.3s ease;
            opacity: 0.7;
        }

        .status-btn:hover {
            opacity: 1;
            transform: scale(1.2);
            background: rgba(255,255,255,0.2);
        }

        .status-btn-todo:hover {
            background: rgba(255, 234, 167, 0.3);
        }

        .status-btn-progress:hover {
            background: rgba(116, 185, 255, 0.3);
        }

        .status-btn-done:hover {
            background: rgba(0, 184, 148, 0.3);
        }

        /* Индикатор загрузки для карточек */
        .task-card.updating {
            opacity: 0.7;
            pointer-events: none;
        }

        .task-card.updating::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #667eea;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Улучшенные уведомления */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 1000;
            animation: slideIn 0.3s ease;
            font-weight: 600;
        }

        .notification.success {
            background: linear-gradient(135deg, #00b894, #00a085);
            color: white;
        }

        .notification.error {
            background: linear-gradient(135deg, #e17055, #d63031);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: white;
        }

        .empty-state h3 {
            font-size: 2rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }

        .empty-state p {
            font-size: 1.1rem;
            opacity: 0.8;
        }

        /* Пагинация */
        .pagination-wrapper {
            display: flex;
            justify-content: center;
            margin-top: 40px;
        }

        .pagination-wrapper .pagination {
            background: white;
            border-radius: 50px;
            padding: 15px 25px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .pagination-wrapper .pagination a,
        .pagination-wrapper .pagination span {
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            color: #667eea;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .pagination-wrapper .pagination a:hover {
            background: #667eea;
            color: white;
            transform: translateY(-1px);
        }

        .pagination-wrapper .pagination .active span {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        /* Оптимизация производительности */
        .task-grid { will-change: transform; }
        .task-card { will-change: transform; }
        .filter-section { will-change: auto; }
        
        /* Анимации только для поддерживающих устройств */
        @media (prefers-reduced-motion: no-preference) {
            .task-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
            .task-card:hover { transform: translateY(-10px); }
            .stat-badge:hover { transform: translateY(-2px); }
        }
        
        /* Оптимизация для мобильных */
        @media (max-width: 768px) {
            .page-header h1 { font-size: 2.5rem; }
            .task-grid { grid-template-columns: 1fr; gap: 20px; }
            .filter-row { flex-direction: column; }
            .filter-group { flex: none; min-width: auto; }
            .task-stats { gap: 10px; }
            .stat-badge { padding: 8px 16px; font-size: 0.9rem; }
            .filter-section { padding: 20px; }
            .task-card { padding: 20px; }
        }
    </style>
    
    <!-- Заголовок страницы в стиле главной -->
    <div class="page-header">
        <h1>📋 Мои задачи</h1>
        <p class="subtitle">Управляйте своими задачами эффективно и красиво</p>
        
        <!-- Статистика задач -->
        <div class="task-stats">
            <div class="stat-badge">
                Всего: <span class="stat-number">{{ $statusCounts['all'] }}</span>
            </div>
            <div class="stat-badge">
                К выполнению: <span class="stat-number">{{ $statusCounts['todo'] }}</span>
            </div>
            <div class="stat-badge">
                В работе: <span class="stat-number">{{ $statusCounts['in_progress'] }}</span>
            </div>
            <div class="stat-badge">
                Выполнено: <span class="stat-number">{{ $statusCounts['done'] }}</span>
            </div>
        </div>
    </div>

    <!-- Фильтр в стиле карточек -->
    <div class="filter-section">
        <h3>🔍 Фильтр и поиск задач</h3>
        <form method="GET" action="{{ route('tasks.index') }}">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="status">Статус задачи</label>
                    <select name="status" id="status" class="filter-input">
                        <option value="">Все задачи</option>
                        <option value="todo" {{ request('status')=='todo'?'selected':'' }}>К выполнению</option>
                        <option value="in_progress" {{ request('status')=='in_progress'?'selected':'' }}>В работе</option>
                        <option value="done" {{ request('status')=='done'?'selected':'' }}>Выполнено</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="search">Поиск по тексту</label>
                    <input type="text" name="search" id="search" class="filter-input" placeholder="Введите название или описание задачи..." value="{{ request('search') }}">
                </div>
                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">Применить фильтр</button>
                    <a href="{{ route('tasks.index') }}" class="btn btn-secondary">Сбросить</a>
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
                    
                    $statusText = match($task->status) {
                        'todo' => 'К ВЫПОЛНЕНИЮ',
                        'in_progress' => 'В РАБОТЕ',
                        'done' => 'ВЫПОЛНЕНО',
                        default => 'К ВЫПОЛНЕНИЮ',
                    };
                @endphp
                <div class="task-card" data-task-id="{{ $task->id }}">
                    <div class="task-title">{{ $task->title }}</div>
                    <div class="task-description">{{ $task->description ?: 'Описание не указано' }}</div>
                    <div class="task-meta">
                        <div class="task-status-section">
                            <span class="task-status {{ $statusClass }}">{{ $statusText }}</span>
                            <div class="status-actions">
                                @if($task->status !== 'todo')
                                    <button class="status-btn status-btn-todo" onclick="changeTaskStatus({{ $task->id }}, 'todo')" title="Вернуть к выполнению">
                                        📝
                                    </button>
                                @endif
                                @if($task->status !== 'in_progress')
                                    <button class="status-btn status-btn-progress" onclick="changeTaskStatus({{ $task->id }}, 'in_progress')" title="Взять в работу">
                                        ⚡
                                    </button>
                                @endif
                                @if($task->status !== 'done')
                                    <button class="status-btn status-btn-done" onclick="changeTaskStatus({{ $task->id }}, 'done')" title="Отметить выполненной">
                                        ✅
                                    </button>
                                @endif
                            </div>
                        </div>
                        <span class="task-date">{{ $task->created_at->format('d.m.Y') }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Пагинация -->
        @if($tasks->hasPages())
            <div class="pagination-wrapper">
                <div class="pagination">
                    {{ $tasks->links() }}
                </div>
            </div>
        @endif
    @else
        <div class="empty-state">
            <h3>🎯 Пока нет задач</h3>
            <p>Создайте свою первую задачу через API или добавьте существующие</p>
        </div>
    @endif
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        'use strict';
        
        const taskCards = document.querySelectorAll('.task-card');
        taskCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
        
        const taskGrid = document.querySelector('.task-grid');
        if (taskGrid && taskGrid.children.length > 50) {
            console.log('Большой список задач, рекомендуется виртуализация');
        }
        
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
        
        const searchInput = document.querySelector('#search');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const form = this.closest('form');
                
                this.style.background = '#f0f8ff';
                
                searchTimeout = setTimeout(() => {
                    this.style.background = '';
                    if (this.value.length >= 2 || this.value.length === 0) {
                        form.submit();
                    }
                }, 800);
            });
        }
        
        const statusSelect = document.querySelector('#status');
        if (statusSelect) {
            statusSelect.addEventListener('change', function() {
                this.closest('form').submit();
            });
        }
        
        taskCards.forEach(card => {
            card.addEventListener('click', function(e) {
                console.log('Клик по задаче:', this.querySelector('.task-title').textContent);
            });
            
            card.style.cursor = 'pointer';
        });
        
        if (navigator.hardwareConcurrency && navigator.hardwareConcurrency < 4) {
            document.documentElement.style.setProperty('--animation-duration', '0s');
        }
    });

    async function changeTaskStatus(taskId, newStatus) {
        const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
        if (!taskCard) return;

        taskCard.classList.add('updating');

        try {
            const response = await fetch(`/api/tasks/${taskId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
                },
                body: JSON.stringify({
                    status: newStatus
                })
            });

            if (response.ok) {
                showNotification('Статус задачи успешно обновлен!', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                throw new Error('Ошибка при обновлении статуса');
            }
        } catch (error) {
            console.error('Ошибка:', error);
            showNotification('Не удалось обновить статус задачи', 'error');
        } finally {
            taskCard.classList.remove('updating');
        }
    }

    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideIn 0.3s ease reverse';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 2500);
    }
</script>
@endsection
