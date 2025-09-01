<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     * Получить список всех задач
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Task::query()->where('user_id', $user?->id);

        // Filters
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

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        if (!in_array($sortBy, ['status', 'created_at', 'deadline', 'priority'])) {
            $sortBy = 'created_at';
        }
        if (!in_array(strtolower($sortDir), ['asc', 'desc'])) {
            $sortDir = 'desc';
        }
        $query->orderBy($sortBy, $sortDir);

        $tasks = $query->get();
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
            'status' => 'required|string|in:todo,in_progress,done',
            'deadline' => 'nullable|date',
            'priority' => 'nullable|integer|min:1|max:3',
        ]);

        $task = Task::create(array_merge($validated, [
            'user_id' => $request->user()?->id,
            'priority' => $validated['priority'] ?? 3,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Задача успешно создана',
            'data' => $task
        ], 201);
    }

    /**
     * Получить конкретную задачу
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $task = Task::where('id', $id)
            ->where('user_id', $request->user()?->id)
            ->first();

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
        $task = Task::where('id', $id)
            ->where('user_id', $request->user()?->id)
            ->first();

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Задача не найдена'
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'status' => 'sometimes|required|string|in:todo,in_progress,done',
            'deadline' => 'sometimes|nullable|date',
            'priority' => 'sometimes|nullable|integer|min:1|max:3',
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
    public function destroy(Request $request, string $id): JsonResponse
    {
        $task = Task::where('id', $id)
            ->where('user_id', $request->user()?->id)
            ->first();

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
