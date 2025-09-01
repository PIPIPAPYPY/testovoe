<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskWebController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $tasks = Task::query()
            ->where('user_id', $user?->id)
            ->latest('created_at')
            ->paginate(12);
        return view('tasks.index', compact('tasks'));
    }
}
