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
            'completed_at' => ['nullable', 'date'],
            'category' => ['nullable', 'string', 'max:100'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'time_spent' => ['nullable', 'integer', 'min:0'],
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
            'completed_at.date' => 'Дата завершения должна быть корректной датой.',
            'category.max' => 'Категория не должна превышать 100 символов.',
            'tags.array' => 'Теги должны быть массивом.',
            'tags.*.string' => 'Каждый тег должен быть строкой.',
            'tags.*.max' => 'Каждый тег не должен превышать 50 символов.',
            'time_spent.integer' => 'Время должно быть целым числом.',
            'time_spent.min' => 'Время не может быть отрицательным.',
        ];
    }
}