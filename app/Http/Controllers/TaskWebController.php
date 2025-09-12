<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskWebController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::query();
        
        // Фильтрация по статусу
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }
        
        // Фильтрация по поиску в заголовке и описании
        if ($request->has('search') && $request->search !== '') {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }
        
        $tasks = $query->orderBy('created_at', 'desc')->get();
        
        // Получаем статистику для фильтра
        $statusCounts = [
            'all' => Task::count(),
            'todo' => Task::where('status', 'todo')->count(),
            'in_progress' => Task::where('status', 'in_progress')->count(),
            'done' => Task::where('status', 'done')->count(),
        ];
        
        return view('tasks.index', compact('tasks', 'statusCounts'));
    }
}
