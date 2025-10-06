<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

/**
 * Политика доступа к задачам
 */
class TaskPolicy
{
    /**
     * Может ли пользователь просматривать любые задачи
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Может ли пользователь просматривать задачу
     */
    public function view(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }

    /**
     * Может ли пользователь создавать задачи
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Может ли пользователь обновлять задачу
     */
    public function update(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }

    /**
     * Может ли пользователь удалять задачу
     */
    public function delete(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }
}