<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос для получения графика создания задач
 */
class GetTaskCreationChartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => ['nullable', 'in:day,week,month'],
            'chart_type' => ['nullable', 'in:line,bar'],
        ];
    }

    public function messages(): array
    {
        return [
            'period.in' => 'Период должен быть: day, week или month.',
            'chart_type.in' => 'Тип графика должен быть: line или bar.',
        ];
    }
}
