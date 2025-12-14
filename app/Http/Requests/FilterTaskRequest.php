<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос для фильтрации задач
 */
class FilterTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:todo,in_progress,done'],
            'priority' => ['nullable', 'integer', 'in:1,2,3'],
            'search' => ['nullable', 'string', 'max:255'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date', 'after_or_equal:created_from'],
            'deadline_from' => ['nullable', 'date'],
            'deadline_to' => ['nullable', 'date', 'after_or_equal:deadline_from'],
            'category' => ['nullable', 'string', 'max:100'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'sort_by' => ['nullable', 'in:created_at,deadline,priority,status,completed_at,category'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Недопустимый статус задачи.',
            'priority.integer' => 'Приоритет должен быть целым числом.',
            'priority.in' => 'Приоритет должен быть от 1 до 3.',
            'search.max' => 'Поисковый запрос не должен превышать 255 символов.',
            'created_from.date' => 'Дата начала создания должна быть корректной датой.',
            'created_to.date' => 'Дата окончания создания должна быть корректной датой.',
            'created_to.after_or_equal' => 'Дата окончания должна быть больше или равна дате начала.',
            'deadline_from.date' => 'Дата начала дедлайна должна быть корректной датой.',
            'deadline_to.date' => 'Дата окончания дедлайна должна быть корректной датой.',
            'deadline_to.after_or_equal' => 'Дата окончания дедлайна должна быть больше или равна дате начала.',
            'category.max' => 'Категория не должна превышать 100 символов.',
            'tags.array' => 'Теги должны быть массивом.',
            'tags.*.string' => 'Каждый тег должен быть строкой.',
            'tags.*.max' => 'Каждый тег не должен превышать 50 символов.',
            'sort_by.in' => 'Недопустимое поле для сортировки.',
            'sort_dir.in' => 'Направление сортировки должно быть asc или desc.',
        ];
    }
}
