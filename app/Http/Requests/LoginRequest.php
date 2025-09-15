<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос для входа пользователя
 * 
 * Валидирует данные для авторизации пользователя
 */
class LoginRequest extends FormRequest
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
            'email' => ['required', 'email'],
            'password' => ['required'],
        ];
    }

    /**
     * Получить пользовательские сообщения об ошибках валидации
     * @return array
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email обязателен для заполнения.',
            'email.email' => 'Введите корректный email адрес.',
            'password.required' => 'Пароль обязателен для заполнения.',
        ];
    }
}

