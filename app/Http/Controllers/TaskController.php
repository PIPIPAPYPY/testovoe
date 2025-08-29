<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    /**
     * Получить список всех задач
     */
    public function index(): JsonResponse
    {
        $tasks = Task::all();
        return response()->json([
            'success' => true,
            'data' => $tasks
        ]);
    }

    /**
     * Создать новую задачу
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string|max:20'
        ]);

        $task = Task::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Задача успешно создана',
            'data' => $task
        ], 201);
    }

    /**
     * Получить конкретную задачу
     */
    public function show(string $id): JsonResponse
    {
        $task = Task::find($id);

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
     * Обновить задачу
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Задача не найдена'
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'status' => 'sometimes|required|string|max:20'
        ]);

        $task->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Задача успешно обновлена',
            'data' => $task
        ]);
    }

    /**
     * Удалить задачу
     */
    public function destroy(string $id): JsonResponse
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Задача не найдена'
            ], 404);
        }

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Задача успешно удалена'
        ]);
    }
}
