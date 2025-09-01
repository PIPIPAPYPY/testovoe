<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskWebController extends Controller
{
    public function index()
    {
        $tasks = Task::query()
            ->select(['id', 'title', 'description', 'status'])
            ->orderByDesc('id')
            ->paginate(12);

        return view('tasks.index', [
            'tasks' => $tasks->items(),
        ]);
    }
}
