<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос для получения графика по приоритетам
 */
class GetPriorityChartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chart_type' => ['nullable', 'in:pie,bar'],
        ];
    }

    public function messages(): array
    {
        return [
            'chart_type.in' => 'Тип графика должен быть: pie или bar.',
        ];
    }
}
