<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Запрос для регистрации пользователя
 * 
 * Валидирует данные для создания нового пользователя
 */
class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    /**
     * Получить пользовательские сообщения об ошибках валидации
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Имя обязательно для заполнения.',
            'name.max' => 'Имя не должно превышать 255 символов.',
            'email.required' => 'Email обязателен для заполнения.',
            'email.email' => 'Введите корректный email адрес.',
            'email.unique' => 'Пользователь с таким email уже существует.',
            'password.required' => 'Пароль обязателен для заполнения.',
            'password.min' => 'Пароль должен содержать минимум 8 символов.',
        ];
    }
}
