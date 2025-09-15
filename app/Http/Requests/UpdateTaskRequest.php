<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос для обновления существующей задачи
 * 
 * Валидирует данные для изменения задачи пользователя
 */
class UpdateTaskRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для выполнения запроса
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Получить правила валидации для запроса
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'status' => 'sometimes|required|string|in:todo,in_progress,done',
            'priority' => 'sometimes|nullable|integer|min:1|max:5',
            'deadline' => 'sometimes|nullable|date',
        ];
    }

    /**
     * Получить пользовательские сообщения об ошибках валидации
     * @return array
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Название задачи обязательно.',
            'title.max' => 'Название не должно превышать 255 символов.',
            'status.required' => 'Статус обязателен.',
            'status.in' => 'Статус должен быть одним из: todo, in_progress, done.',
            'priority.min' => 'Приоритет должен быть не менее 1.',
            'priority.max' => 'Приоритет должен быть не более 5.',
            'deadline.date' => 'Некорректная дата дедлайна.',
        ];
    }
}

