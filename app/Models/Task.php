<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель задачи пользователя
 * 
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property int $priority
 * @property int $user_id
 * @property \Carbon\Carbon|null $deadline
 * @property \Carbon\Carbon|null $completed_at
 * @property string|null $category
 * @property array|null $tags
 * @property int|null $time_spent
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 
        'description', 
        'status', 
        'user_id', 
        'priority', 
        'deadline',
        'completed_at',
        'category',
        'tags',
        'time_spent'
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'completed_at' => 'datetime',
        'priority' => 'integer',
        'user_id' => 'integer',
        'time_spent' => 'integer',
        'tags' => 'array',
    ];

    protected $attributes = [
        'status' => 'todo',
        'priority' => 2,
    ];

    /**
     * Статусы задач
     */
    public const STATUS_TODO = 'todo';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DONE = 'done';

    /**
     * Приоритеты задач
     */
    public const PRIORITY_HIGH = 1;
    public const PRIORITY_MEDIUM = 2;
    public const PRIORITY_LOW = 3;

    /**
     * Получить задачи конкретного пользователя
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Фильтр по статусу
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Фильтр по приоритету
     */
    public function scopeByPriority(Builder $query, int $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    /**
     * Фильтр по просроченным задачам
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('deadline', '<', now())
                    ->where('status', '!=', self::STATUS_DONE);
    }

    /**
     * Фильтр по выполненным задачам
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DONE);
    }

    /**
     * Получить пользователя, которому принадлежит задача
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Проверить, просрочена ли задача
     */
    public function isOverdue(): bool
    {
        return $this->deadline && 
               $this->deadline->isPast() && 
               $this->status !== self::STATUS_DONE;
    }

    /**
     * Проверить, выполнена ли задача
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_DONE;
    }

    /**
     * Получить человекочитаемый статус
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_TODO => 'К выполнению',
            self::STATUS_IN_PROGRESS => 'В работе',
            self::STATUS_DONE => 'Выполнено',
            default => 'Неизвестно',
        };
    }

    /**
     * Получить человекочитаемый приоритет
     */
    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_HIGH => 'Высокий',
            self::PRIORITY_MEDIUM => 'Средний',
            self::PRIORITY_LOW => 'Низкий',
            default => 'Неизвестно',
        };
    }
}
