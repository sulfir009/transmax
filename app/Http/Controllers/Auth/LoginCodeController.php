<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoginCodeController extends Controller
{
    /**
     * Display the login code verification page.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function show(Request $request)
    {
        // Получаем тип входа (phone/email) и значение из сессии или параметров
        $contactType = session('auth.contact_type', 'phone'); // phone или email
        $contactValue = session('auth.contact_value', '+380733456789'); // дефолтное значение для примера
        
        return view('auth.login-code', [
            'contactType' => $contactType,
            'contactValue' => $contactValue,
        ]);
    }
    
    /**
     * Verify the login code.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:4',
            'contact_type' => 'required|in:phone,email',
        ]);
        
        // TODO: Реализовать логику проверки кода
        // Это будет зависеть от вашей существующей системы аутентификации
        
        return response()->json([
            'success' => true,
            'message' => __('dictionary.MSG_MSG_LOGIN_KOD_VEREN')
        ]);
    }
}
