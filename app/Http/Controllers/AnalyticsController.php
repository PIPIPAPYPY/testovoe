<?php

namespace App\Http\Controllers;

use App\Services\Analytics\AnalyticsServiceInterface;
use App\Services\Analytics\Charts\ChartFactory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Контроллер аналитики задач
 * 
 * Обеспечивает отображение аналитических данных и графиков
 * для веб-интерфейса и API
 */
class AnalyticsController extends Controller
{
    private AnalyticsServiceInterface $analyticsService;

    public function __construct(AnalyticsServiceInterface $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Отобразить страницу аналитики
     * 
     * @return View
     */
    public function index(): View
    {
        $userId = Auth::id();
        $overallStats = $this->analyticsService->getOverallStats($userId);
        
        // Временная отладка
        \Log::info('Analytics stats:', $overallStats);
        
        // Если ключ отсутствует, добавляем его временно
        if (!array_key_exists('completed_last_30_days', $overallStats)) {
            $overallStats['completed_last_30_days'] = 0;
        }
        
        return view('analytics.index', compact('overallStats'));
    }

    /**
     * Получить данные для графика создания задач
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getTaskCreationChart(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'period' => 'nullable|in:day,week,month',
                'chart_type' => 'nullable|in:line,bar'
            ]);

            $userId = Auth::id();
            $period = $request->input('period', 'month');
            $chartType = $request->input('chart_type', 'line');

            $data = $this->analyticsService->getTaskCreationStats($userId, $period);
            
            $chart = ChartFactory::create(
                $chartType,
                'Создание задач за период',
                'Период',
                'Количество задач'
            );

            return response()->json([
                'success' => true,
                'data' => $chart->getData($data),
                'config' => $chart->getConfig()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения данных: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить данные для круговой диаграммы выполнения
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getCompletionChart(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'chart_type' => 'nullable|in:pie,bar'
            ]);

            $userId = Auth::id();
            $chartType = $request->input('chart_type', 'pie');

            $data = $this->analyticsService->getCompletionStats($userId);
            
            $chart = ChartFactory::create(
                $chartType,
                'Выполненные vs Невыполненные задачи',
                'Статус',
                'Количество задач'
            );

            return response()->json([
                'success' => true,
                'data' => $chart->getData($data),
                'config' => $chart->getConfig()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения данных по выполнению: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить данные для графика по приоритетам
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getPriorityChart(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'chart_type' => 'nullable|in:pie,bar'
            ]);

            $userId = Auth::id();
            $chartType = $request->input('chart_type', 'bar');

            $data = $this->analyticsService->getPriorityStats($userId);
            
            $chart = ChartFactory::create(
                $chartType,
                'Распределение задач по приоритетам',
                'Приоритеты',
                'Количество задач'
            );

            return response()->json([
                'success' => true,
                'data' => $chart->getData($data),
                'config' => $chart->getConfig()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения данных по приоритетам: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить данные для тепловой карты активности по дням недели
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getWeeklyActivityChart(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();
            $data = $this->analyticsService->getWeeklyActivityStats($userId);
            
            $chart = ChartFactory::create(
                'bar',
                'Активность по дням недели',
                'Дни недели',
                'Количество задач'
            );

            return response()->json([
                'success' => true,
                'data' => $chart->getData($data),
                'config' => $chart->getConfig()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения данных по дням недели: ' . $e->getMessage()
            ], 500);
        }
    }



    /**
     * Получить общую статистику пользователя
     * 
     * @return JsonResponse
     */
    public function getOverallStats(): JsonResponse
    {
        try {
            $userId = Auth::id();
            $stats = $this->analyticsService->getOverallStats($userId);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения общей статистики: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить данные для графика выполненных задач
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getCompletedTasksChart(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'period' => 'nullable|in:day,week,month',
                'chart_type' => 'nullable|in:line,bar'
            ]);

            $userId = Auth::id();
            $period = $request->input('period', 'month');
            $chartType = $request->input('chart_type', 'line');

            $data = $this->analyticsService->getTaskCreationStats($userId, $period);
            
            $chart = ChartFactory::create(
                $chartType,
                'Выполненные задачи за период',
                'Период',
                'Количество задач'
            );

            return response()->json([
                'success' => true,
                'data' => $chart->getData($data),
                'config' => $chart->getConfig()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения данных по выполненным задачам: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить данные для графика по категориям
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getCategoryChart(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();
            
            // Пока возвращаем заглушку, так как категории не реализованы
            $data = [
                ['category' => 'Работа', 'count' => 15],
                ['category' => 'Личное', 'count' => 8],
                ['category' => 'Учеба', 'count' => 5]
            ];
            
            $chart = ChartFactory::create(
                'pie',
                'Задачи по категориям',
                'Категории',
                'Количество задач'
            );

            return response()->json([
                'success' => true,
                'data' => $chart->getData($data),
                'config' => $chart->getConfig()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения данных по категориям: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить данные для графика по тегам
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getTagChart(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();
            
            // Пока возвращаем заглушку, так как теги не реализованы
            $data = [
                ['tag' => 'срочно', 'count' => 12],
                ['tag' => 'важно', 'count' => 9],
                ['tag' => 'проект', 'count' => 6],
                ['tag' => 'встреча', 'count' => 4]
            ];
            
            $chart = ChartFactory::create(
                'bar',
                'Задачи по тегам',
                'Теги',
                'Количество задач'
            );

            return response()->json([
                'success' => true,
                'data' => $chart->getData($data),
                'config' => $chart->getConfig()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения данных по тегам: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить данные для графика продуктивных дней
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getProductiveDaysChart(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();
            $data = $this->analyticsService->getWeeklyActivityStats($userId);
            
            $chart = ChartFactory::create(
                'bar',
                'Продуктивные дни недели',
                'Дни недели',
                'Количество задач'
            );

            return response()->json([
                'success' => true,
                'data' => $chart->getData($data),
                'config' => $chart->getConfig()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения данных по продуктивным дням: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить доступные категории
     * 
     * @return JsonResponse
     */
    public function getAvailableCategories(): JsonResponse
    {
        try {
            // Пока возвращаем заглушку
            $categories = ['Работа', 'Личное', 'Учеба', 'Проекты', 'Встречи'];

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения категорий: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить доступные теги
     * 
     * @return JsonResponse
     */
    public function getAvailableTags(): JsonResponse
    {
        try {
            // Пока возвращаем заглушку
            $tags = ['срочно', 'важно', 'проект', 'встреча', 'звонок', 'email', 'документы'];

            return response()->json([
                'success' => true,
                'data' => $tags
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения тегов: ' . $e->getMessage()
            ], 500);
        }
    }
}