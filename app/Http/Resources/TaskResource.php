<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API ресурс для задач
 * 
 * Стандартизирует формат вывода задач в API
 */
class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'deadline' => $this->deadline?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'status_label' => $this->getStatusLabel(),
            'priority_label' => $this->getPriorityLabel(),
            'is_overdue' => $this->isOverdue(),
        ];
    }

    /**
     * Получить человекочитаемый статус
     */
    private function getStatusLabel(): string
    {
        return match ($this->status) {
            'todo' => 'К выполнению',
            'in_progress' => 'В работе',
            'done' => 'Выполнено',
            default => 'Неизвестно',
        };
    }

    /**
     * Получить человекочитаемый приоритет
     */
    private function getPriorityLabel(): string
    {
        return match ($this->priority) {
            1 => 'Высокий',
            2 => 'Средний',
            3 => 'Низкий',
            default => 'Неизвестно',
        };
    }

    /**
     * Проверить, просрочена ли задача
     */
    private function isOverdue(): bool
    {
        return $this->deadline && 
               $this->deadline->isPast() && 
               $this->status !== 'done';
    }
}