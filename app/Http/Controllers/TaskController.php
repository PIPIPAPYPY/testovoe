<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;

/**
 * Контроллер для управления задачами через API
 * 
 * Обеспечивает CRUD операции для задач пользователя
 */
class TaskController extends Controller
{
    /**
     * Получить список всех задач пользователя с фильтрацией и сортировкой
     * @param Request $request Запрос с параметрами фильтрации
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Task::forUser(Auth::id());

        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);

        $tasks = $query->get();

        return response()->json([
            'success' => true,
            'data' => $tasks
        ]);
    }

    /**
     * Применить фильтры к запросу задач
     * @param \Illuminate\Database\Eloquent\Builder $query Построитель запроса
     * @param Request $request Запрос с параметрами фильтрации
     * @return void
     */
    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->date('created_from'));
        }

        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->date('created_to'));
        }

        if ($request->filled('deadline_from')) {
            $query->where('deadline', '>=', $request->date('deadline_from'));
        }

        if ($request->filled('deadline_to')) {
            $query->where('deadline', '<=', $request->date('deadline_to'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', (int) $request->input('priority'));
        }
    }

    /**
     * Применить сортировку к запросу задач
     * @param \Illuminate\Database\Eloquent\Builder $query Построитель запроса
     * @param Request $request Запрос с параметрами сортировки
     * @return void
     */
    private function applySorting($query, Request $request): void
    {
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedSortFields = ['status', 'created_at', 'deadline', 'priority'];
        $allowedSortDirections = ['asc', 'desc'];

        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }

        if (!in_array(strtolower($sortDir), $allowedSortDirections)) {
            $sortDir = 'desc';
        }

        $query->orderBy($sortBy, $sortDir);
    }

    /**
     * Создать новую задачу
     * @param StoreTaskRequest $request Валидированный запрос с данными задачи
     * @return JsonResponse
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $task = Task::create(array_merge($validated, [
            'user_id' => Auth::id(),
            'status' => $validated['status'] ?? 'todo',
            'priority' => $validated['priority'] ?? 3,
        ]));


        $this->clearUserStatsCache(Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Задача успешно создана',
            'data' => $task
        ], 201);
    }

    /**
     * Получить конкретную задачу по ID
     * @param Request $request HTTP запрос
     * @param string $id Идентификатор задачи
     * @return JsonResponse
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $task = Task::forUser(Auth::id())->find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Задача не найдена'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $task
        ]);
    }

    /**
     * Обновить существующую задачу
     * @param UpdateTaskRequest $request Валидированный запрос с новыми данными
     * @param string $id Идентификатор задачи
     * @return JsonResponse
     */
    public function update(UpdateTaskRequest $request, string $id): JsonResponse
    {
        $task = Task::forUser(Auth::id())->find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Задача не найдена'
            ], 404);
        }

        $validated = $request->validated();
        $task->update($validated);


        $this->clearUserStatsCache(Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Задача успешно обновлена',
            'data' => $task
        ]);
    }

    /**
     * Удалить задачу по ID
     * @param Request $request HTTP запрос
     * @param string $id Идентификатор задачи
     * @return JsonResponse
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $task = Task::forUser(Auth::id())->find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Задача не найдена'
            ], 404);
        }

        $task->delete();


        $this->clearUserStatsCache(Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Задача успешно удалена'
        ]);
    }

    /**
     * Очистить кэш статистики задач пользователя
     * @param int $userId Идентификатор пользователя
     * @return void
     */
    private function clearUserStatsCache(int $userId): void
    {
        Cache::forget("user_{$userId}_task_counts");
    }
}
