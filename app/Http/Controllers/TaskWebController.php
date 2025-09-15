<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Task;

/**
 * Контроллер для веб-интерфейса задач
 * 
 * Обеспечивает отображение задач в браузере с пагинацией и фильтрацией
 */
class TaskWebController extends Controller
{
    /**
     * Отобразить список задач пользователя с пагинацией
     * @param Request $request HTTP запрос с параметрами фильтрации
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = $this->getUserTasksQuery();
        
        $this->applyFilters($query, $request);
        $this->applySearch($query, $request);
        
        $tasks = $query->orderBy('created_at', 'desc')
                      ->paginate(10)
                      ->appends($request->all());

        $statusCounts = $this->getStatusCounts();

        return view('tasks.index', compact('tasks', 'statusCounts'));
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

    /**
     * Получить запрос для задач текущего пользователя
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getUserTasksQuery()
    {
        return Task::forUser(Auth::id());
    }

    /**
     * Применить фильтры к запросу задач
     * @param \Illuminate\Database\Eloquent\Builder $query Построитель запроса
     * @param Request $request HTTP запрос с параметрами фильтрации
     * @return void
     */
    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
    }

    /**
     * Применить поиск к запросу задач по названию и описанию
     * @param \Illuminate\Database\Eloquent\Builder $query Построитель запроса
     * @param Request $request HTTP запрос с параметром поиска
     * @return void
     */
    private function applySearch($query, Request $request): void
    {
        if (!$request->filled('search')) {
            return;
        }

        $searchTerm = trim($request->search);
        if (empty($searchTerm)) {
            return;
        }

        $searchTerm = preg_replace('/[\x00-\x1F\x7F]/u', '', $searchTerm);
        
        if (empty($searchTerm)) {
            return;
        }

        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchTerm);
        
        $words = preg_split('/\s+/u', $escaped, -1, PREG_SPLIT_NO_EMPTY);
        
        if (empty($words)) {
            return;
        }

        $query->where(function ($q) use ($words) {
            foreach ($words as $word) {
                $like = '%' . mb_strtolower($word) . '%';
                $q->where(function ($q2) use ($like) {
                    $q2->whereRaw('LOWER(title) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(description) LIKE ?', [$like]);
                });
            }
        });
    }

    /**
     * Получить статистику по статусам задач с кэшированием
     * @return array Массив с количеством задач по статусам
     */
    private function getStatusCounts(): array
    {
        $userId = Auth::id();
        $cacheKey = "user_{$userId}_task_counts";
        
        return Cache::remember($cacheKey, 300, function () use ($userId) {
            $counts = Task::forUser($userId)
                ->selectRaw('
                    COUNT(*) as all_count,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as todo_count,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_progress_count,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as done_count
                ', ['todo', 'in_progress', 'done'])
                ->first();
            
            return [
                'all' => $counts->all_count,
                'todo' => $counts->todo_count,
                'in_progress' => $counts->in_progress_count,
                'done' => $counts->done_count,
            ];
        });
    }
}
