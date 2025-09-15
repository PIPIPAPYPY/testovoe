<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\LoginRequest;

/**
 * Контроллер для веб-аутентификации
 * 
 * Обеспечивает вход и выход пользователей через веб-интерфейс
 */
class WebAuthController extends Controller
{
    /**
     * Конструктор контроллера
     * @return void
     */
    public function __construct()
    {
        $this->middleware('web');
    }

    /**
     * Показать форму входа
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Выполнить вход пользователя
     * @param LoginRequest $request Валидированный запрос с данными входа
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            return redirect()->intended(route('tasks.index'));
        }

        return back()->withErrors([
            'email' => 'Неверные учетные данные.',
        ])->onlyInput('email');
    }

    /**
     * Выполнить выход пользователя
     * @param Request $request HTTP запрос
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
