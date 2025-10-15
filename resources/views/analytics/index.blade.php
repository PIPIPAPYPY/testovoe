@extends('layouts.app')

@section('title', 'Аналитика задач - Task Management')
@section('description', 'Анализируйте свою продуктивность и эффективность выполнения задач')

@section('content')
<style>
    /* Стили в едином стиле с главной страницей */
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        min-height: 100vh;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .main.container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 40px 20px;
    }

    /* Заголовок страницы */
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
        margin-bottom: 30px;
    }

    /* Общая статистика */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: white;
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        position: relative;
        overflow: hidden;
        transition: transform 0.3s ease;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2);
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-icon {
        font-size: 2.5rem;
        margin-bottom: 15px;
        display: block;
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 5px;
    }

    .stat-label {
        color: #666;
        font-size: 1rem;
        font-weight: 600;
    }

    .stat-change {
        font-size: 0.9rem;
        margin-top: 10px;
        padding: 5px 10px;
        border-radius: 15px;
        display: inline-block;
    }

    .stat-change.positive {
        background: rgba(0, 184, 148, 0.1);
        color: #00b894;
    }

    .stat-change.neutral {
        background: rgba(116, 185, 255, 0.1);
        color: #74b9ff;
    }

    /* Фильтры */
    .filters-section {
        background: white;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }

    .filters-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2);
    }

    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        align-items: end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
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

    /* Графики */
    .charts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
        gap: 30px;
        margin-bottom: 40px;
    }

    .chart-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        position: relative;
        overflow: hidden;
        min-height: 400px;
    }

    .chart-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2);
    }

    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .chart-title {
        font-size: 1.4rem;
        font-weight: 600;
        color: #333;
    }

    .chart-controls {
        display: flex;
        gap: 10px;
    }

    .chart-type-btn {
        padding: 6px 12px;
        border: 2px solid #e9ecef;
        background: white;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.8rem;
    }

    .chart-type-btn.active {
        border-color: #667eea;
        background: #667eea;
        color: white;
    }

    .chart-container {
        position: relative;
        height: 350px;
        width: 100%;
    }

    .loading-spinner {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 350px;
        font-size: 1.2rem;
        color: #666;
    }

    .loading-spinner::before {
        content: '';
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-right: 15px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Адаптивность */
    @media (max-width: 768px) {
        .page-header h1 { font-size: 2.5rem; }
        .charts-grid { grid-template-columns: 1fr; }
        .filters-grid { grid-template-columns: 1fr; }
        .stats-grid { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
        .chart-card { padding: 20px; min-height: 300px; }
        .chart-container { height: 250px; }
    }
</style>

<!-- Заголовок страницы -->
<div class="page-header">
    <h1>📊 Аналитика задач</h1>
    <p class="subtitle">Анализируйте свою продуктивность и отслеживайте прогресс</p>
</div>

<!-- Общая статистика -->
<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-icon">📋</span>
        <div class="stat-value">{{ $overallStats['total_tasks'] }}</div>
        <div class="stat-label">Всего задач</div>
    </div>

    <div class="stat-card">
        <span class="stat-icon">✅</span>
        <div class="stat-value">{{ $overallStats['completed_tasks'] }}</div>
        <div class="stat-label">Выполнено</div>
        <div class="stat-change positive">{{ $overallStats['completion_rate'] }}% завершено</div>
    </div>

    <div class="stat-card">
        <span class="stat-icon">⚡</span>
        <div class="stat-value">{{ $overallStats['in_progress_tasks'] }}</div>
        <div class="stat-label">В работе</div>
    </div>

    <div class="stat-card">
        <span class="stat-icon">📝</span>
        <div class="stat-value">{{ $overallStats['todo_tasks'] }}</div>
        <div class="stat-label">К выполнению</div>
    </div>

    <div class="stat-card">
        <span class="stat-icon">🔥</span>
        <div class="stat-value">{{ $overallStats['completed_last_30_days'] }}</div>
        <div class="stat-label">Выполнено за 30 дней</div>
    </div>
</div>

<!-- Фильтры -->
<div class="filters-section">
    <h3 style="margin-bottom: 20px; color: #333;">🔍 Фильтры для графиков</h3>
    <div class="filters-grid">
        <div class="filter-group">
            <label for="period">Период</label>
            <select id="period" class="filter-input">
                <option value="month">Месяц</option>
                <option value="week">Неделя</option>
                <option value="year">Год</option>
            </select>
        </div>

        <div class="filter-group">
            <button id="apply-filters" class="btn btn-primary">Применить фильтры</button>
        </div>
    </div>
</div>

<!-- Графики -->
<div class="charts-grid">
    <!-- График создания задач -->
    <div class="chart-card">
        <div class="chart-header">
            <h3 class="chart-title">Создание задач за период</h3>
            <div class="chart-controls">
                <button class="chart-type-btn active" data-chart="creation" data-type="line">Линия</button>
                <button class="chart-type-btn" data-chart="creation" data-type="bar">Столбцы</button>
            </div>
        </div>
        <div class="chart-container">
            <canvas id="creationChart"></canvas>
            <div id="creationLoading" class="loading-spinner">Загрузка данных...</div>
        </div>
    </div>

    <!-- Круговая диаграмма выполнения -->
    <div class="chart-card">
        <div class="chart-header">
            <h3 class="chart-title">Выполненные vs Невыполненные</h3>
            <div class="chart-controls">
                <button class="chart-type-btn active" data-chart="completion" data-type="pie">Круг</button>
                <button class="chart-type-btn" data-chart="completion" data-type="bar">Столбцы</button>
            </div>
        </div>
        <div class="chart-container">
            <canvas id="completionChart"></canvas>
            <div id="completionLoading" class="loading-spinner">Загрузка данных...</div>
        </div>
    </div>

    <!-- График по приоритетам -->
    <div class="chart-card">
        <div class="chart-header">
            <h3 class="chart-title">Задачи по приоритетам</h3>
            <div class="chart-controls">
                <button class="chart-type-btn active" data-chart="priority" data-type="bar">Столбцы</button>
                <button class="chart-type-btn" data-chart="priority" data-type="pie">Круг</button>
            </div>
        </div>
        <div class="chart-container">
            <canvas id="priorityChart"></canvas>
            <div id="priorityLoading" class="loading-spinner">Загрузка данных...</div>
        </div>
    </div>

    <!-- Активность по дням недели -->
    <div class="chart-card">
        <div class="chart-header">
            <h3 class="chart-title">Активность по дням недели</h3>
        </div>
        <div class="chart-container">
            <canvas id="weeklyChart"></canvas>
            <div id="weeklyLoading" class="loading-spinner">Загрузка данных...</div>
        </div>
    </div>


</div>
@endsection

@section('scripts')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // Хранилище для графиков
    const charts = {};

    // Инициализация
    initializeCharts();
    setupEventListeners();

    function initializeCharts() {
        loadChart('creation', 'line');
        loadChart('completion', 'pie');
        loadChart('priority', 'bar');
        loadChart('weekly', 'bar');

    }

    function setupEventListeners() {
        // Обработчики для кнопок типов графиков
        document.querySelectorAll('.chart-type-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const chartName = this.dataset.chart;
                const chartType = this.dataset.type;

                // Обновляем активную кнопку
                this.parentElement.querySelectorAll('.chart-type-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                // Перезагружаем график
                loadChart(chartName, chartType);
            });
        });

        // Обработчик для применения фильтров
        document.getElementById('apply-filters').addEventListener('click', function() {
            initializeCharts();
        });
    }

    async function loadChart(chartName, chartType) {
        const canvas = document.getElementById(chartName + 'Chart');
        const loading = document.getElementById(chartName + 'Loading');

        if (!canvas || !loading) return;

        // Показываем загрузку
        canvas.style.display = 'none';
        loading.style.display = 'flex';

        try {
            // Уничтожаем существующий график
            if (charts[chartName]) {
                charts[chartName].destroy();
            }

            // Получаем данные
            const data = await fetchChartData(chartName, chartType);

            if (data.success) {
                // Проверяем, есть ли данные для отображения
                if (data.data && (data.data.labels?.length > 0 || data.data.datasets?.some(d => d.data?.length > 0))) {
                    // Создаем новый график
                    const ctx = canvas.getContext('2d');
                    charts[chartName] = new Chart(ctx, {
                        ...data.config,
                        data: data.data
                    });

                    // Скрываем загрузку
                    loading.style.display = 'none';
                    canvas.style.display = 'block';
                } else {
                    // Нет данных для отображения
                    loading.innerHTML = '<div style="color: #999; text-align: center;"><div style="font-size: 3rem; margin-bottom: 10px;">📊</div><div>Нет данных для отображения</div><div style="font-size: 0.9rem; margin-top: 5px;">Создайте задачи с категориями и тегами</div></div>';
                }
            } else {
                throw new Error(data.message || 'Ошибка загрузки данных');
            }
        } catch (error) {
            console.error('Ошибка загрузки графика:', error);
            loading.innerHTML = `
                <div style="color: #e74c3c; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 10px;">⚠️</div>
                    <div>Ошибка загрузки данных</div>
                    <div style="font-size: 0.8rem; margin-top: 5px; color: #999;">
                        ${error.message}
                    </div>
                    <button onclick="location.reload()" style="margin-top: 10px; padding: 5px 10px; border: none; background: #667eea; color: white; border-radius: 5px; cursor: pointer;">
                        Обновить страницу
                    </button>
                </div>
            `;
        }
    }

    async function fetchChartData(chartName, chartType) {
        const filters = getFilters();
        const params = new URLSearchParams({
            chart_type: chartType,
            ...filters
        });

        const endpoints = {
            'creation': '/analytics/task-creation-chart',
            'completion': '/analytics/completion-chart',
            'priority': '/analytics/priority-chart',
            'weekly': '/analytics/weekly-activity-chart'
        };

        const url = `${endpoints[chartName]}?${params}`;
        console.log('Fetching chart data from:', url);

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            console.error('Response not OK:', response.status, response.statusText);
            const errorText = await response.text();
            console.error('Error response:', errorText);
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        console.log('Chart data received:', data);
        return data;
    }

    function getFilters() {
        return {
            period: document.getElementById('period').value
        };
    }
});
</script>
@endsection
