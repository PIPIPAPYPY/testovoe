<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;

/**
 * Контроллер для API аутентификации
 * 
 * Обеспечивает регистрацию, вход и выход через API с токенами Sanctum
 */
class AuthController extends Controller
{

    /**
     * Выполнить вход пользователя через API
     * @param LoginRequest $request Валидированный запрос с данными входа
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Неверные учетные данные.'
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Успешный вход',
            'user' => $user,
            'token' => $token
        ]);
    }

    /**
     * Зарегистрировать нового пользователя
     * @param RegisterRequest $request Валидированный запрос с данными регистрации
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function register(RegisterRequest $request)
    {
        $credentials = $request->validated();

        $user = \App\Models\User::create([
            'name' => $credentials['name'],
            'email' => $credentials['email'],
            'password' => bcrypt($credentials['password']),
        ]);

        Auth::login($user);

        if ($request->expectsJson()) {
            $token = $user->createToken('api-token')->plainTextToken;
            return response()->json([
                'success' => true,
                'message' => 'Регистрация успешна',
                'user' => $user,
                'token' => $token
            ], 201);
        }

        return redirect()->intended(route('tasks.index'));
    }

    /**
     * Выполнить выход пользователя
     * @param Request $request HTTP запрос
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {

        if ($request->expectsJson()) {
            $user = $request->user();
            if ($user) {
                $user->tokens()->delete();
            }
            return response()->json([
                'success' => true,
                'message' => 'Успешный выход'
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();


        return redirect('/')->with('success', 'Вы успешно вышли из аккаунта.');
    }
}

