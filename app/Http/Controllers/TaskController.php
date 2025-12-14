<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

/**
 * API контроллер для управления задачами
 * 
 * Следует принципам REST API и SOLID
 */
class TaskController extends Controller
{
    public function __construct(
        private TaskService $taskService
    ) {
        $this->authorizeResource(Task::class, 'task');
    }

    /**
     * Получить список задач пользователя
     */
    public function index(FilterTaskRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $tasks = $this->taskService->getUserTasks(Auth::id(), $filters, 50);

        return TaskResource::collection($tasks);
    }

    /**
     * Создать новую задачу
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->taskService->createTask(
            Auth::id(), 
            $request->validated()
        );

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Получить конкретную задачу
     */
    public function show(Task $task): TaskResource
    {
        return new TaskResource($task);
    }

    /**
     * Обновить задачу
     */
    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $updatedTask = $this->taskService->updateTask(
            $task, 
            $request->validated()
        );

        return new TaskResource($updatedTask);
    }

    /**
     * Удалить задачу
     */
    public function destroy(Task $task): JsonResponse
    {
        $this->taskService->deleteTask($task);

        return response()->json([
            'success' => true,
            'message' => 'Задача успешно удалена'
        ]);
    }
}
