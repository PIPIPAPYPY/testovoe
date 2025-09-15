<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * Модель задачи пользователя
 * 
 * Содержит информацию о задачах: название, описание, статус, приоритет, дедлайн
 */
class Task extends Model
{
    protected $fillable = ['title', 'description', 'status', 'user_id', 'priority', 'deadline'];

    protected $casts = [
        'deadline' => 'datetime',
        'priority' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * Получить задачи конкретного пользователя
     * @param \Illuminate\Database\Eloquent\Builder $query Построитель запроса
     * @param int $userId Идентификатор пользователя
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Очистить кэш статистики при изменении модели
     * @return void
     */
    protected static function booted()
    {
        static::created(function ($task) {
            Cache::forget("user_{$task->user_id}_task_counts");
        });

        static::updated(function ($task) {
            Cache::forget("user_{$task->user_id}_task_counts");
        });

        static::deleted(function ($task) {
            Cache::forget("user_{$task->user_id}_task_counts");
        });
    }

    /**
     * Получить пользователя, которому принадлежит задача
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
