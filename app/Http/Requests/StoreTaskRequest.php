<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос для создания задачи
 */
class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'priority' => ['nullable', 'integer', 'in:1,2,3'],
            'status' => ['nullable', 'in:todo,in_progress,done'],
            'deadline' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Название задачи обязательно для заполнения.',
            'title.max' => 'Название задачи не должно превышать 255 символов.',
            'description.max' => 'Описание не должно превышать 1000 символов.',
            'priority.in' => 'Приоритет должен быть от 1 до 3.',
            'status.in' => 'Недопустимый статус задачи.',
            'deadline.date' => 'Дедлайн должен быть корректной датой.',
            'deadline.after' => 'Дедлайн должен быть в будущем.',
        ];
    }
}