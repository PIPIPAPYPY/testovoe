<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Контроллер для веб-интерфейса задач
 * 
 * Следует принципам SOLID:
 * - Single Responsibility: только веб-интерфейс
 * - Dependency Inversion: зависит от абстракций (TaskService)
 */
class TaskWebController extends Controller
{
    public function __construct(
        private TaskService $taskService
    ) {
        $this->authorizeResource(Task::class, 'task');
    }

    /**
     * Отобразить список задач пользователя
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'search', 'priority']);
        $tasks = $this->taskService->getUserTasks(Auth::id(), $filters);
        $statusCounts = $this->taskService->getStatusCounts(Auth::id());

        return view('tasks.index', compact('tasks', 'statusCounts'));
    }

    /**
     * Создать новую задачу
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        try {
            $task = $this->taskService->createTask(
                Auth::id(), 
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Задача успешно создана',
                'task' => $task
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании задачи: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Обновить задачу
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        try {
            $updatedTask = $this->taskService->updateTask(
                $task, 
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Задача успешно обновлена',
                'task' => $updatedTask
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении задачи: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Удалить задачу
     */
    public function destroy(Task $task): JsonResponse
    {
        try {
            $this->taskService->deleteTask($task);

            return response()->json([
                'success' => true,
                'message' => 'Задача успешно удалена'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении задачи: ' . $e->getMessage()
            ], 500);
        }
    }
}
