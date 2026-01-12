<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Service\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    /**
     * Display the login page.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show()
    {
        // Если пользователь уже авторизован, редиректим
        if (User::isAuth()) {
            if (session()->has('order')) {
                return redirect('/majbutni-pozdki/');
            }
            return redirect('/majbutni-pozdki/');
        }
        
        // Получаем текст для страницы логина
        $loginPageTxt = DB::table('mt_txt_blocks')
            ->where('id', 6)
            ->value('text_' . \App\Service\Site::lang());
        
        // Google OAuth параметры
        $googleAuthParams = [
            'client_id' => '1047739033954-v7dqa3vbh69hu7j0drp36vvj2mbs6un3.apps.googleusercontent.com',
            'redirect_uri' => 'https://www.maxtransltd.com/social/google.php',
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
            'state' => '123'
        ];
        $googleAuthLink = 'https://accounts.google.com/o/oauth2/auth?' . urldecode(http_build_query($googleAuthParams));
        
        // Facebook OAuth параметры
        $facebookAuthParams = [
            'client_id' => '740501071244051',
            'redirect_uri' => 'https://www.maxtransltd.com/social/facebook.php',
            'scope' => 'email',
            'response_type' => 'code',
            'state' => '123'
        ];
        $facebookAuthLink = 'https://www.facebook.com/dialog/oauth?' . urldecode(http_build_query($facebookAuthParams));
        
        return view('auth.login', [
            'loginPageTxt' => $loginPageTxt,
            'googleAuthLink' => $googleAuthLink,
            'facebookAuthLink' => $facebookAuthLink,
        ]);
    }
    
    /**
     * Handle login request.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|email|max:255',
            'password' => 'required|string',
        ]);
        
        $email = trim($request->input('login'));
        $password = trim($request->input('password'));
        
        try {
            // Поиск пользователя по email
            $user = DB::table('mt_clients')
                ->select(['id', 'name', 'email', 'password'])
                ->where('email', $email)
                ->where('active', 1)
                ->first();
            
            if (!$user) {
                // Email не найден
                return response()->json([
                    'data' => 'email_not_found'
                ]);
            }
            
            // Проверка пароля
            if (!password_verify($password, $user->password)) {
                // Неправильный пароль
                return response()->json([
                    'data' => __('dictionary.MSG_MSG_LOGIN_NEVERNYE_DANNYE')
                ]);
            }
            
            // Генерация crypt токена
            $crypt = hash('sha512', uniqid() . time());
            
            // Обновление crypt токена в БД
            DB::table('mt_clients')
                ->where('id', $user->id)
                ->update([
                    'crypt' => $crypt,
                    'last_auth_date' => now()
                ]);
            
            // Сохранение данных в сессию
            session([
                'user' => [
                    'auth' => true,
                    'crypt' => $crypt,
                    'isAuth' => true,
                ],
            ]);
            
            // Совместимость с legacy кодом
            $_SESSION['user']['auth'] = true;
            $_SESSION['user']['crypt'] = $crypt;
            $_SESSION['user']['isAuth'] = true;
            
            // Вызов статического метода login для совместимости
            User::login();
            
            return response()->json([
                'data' => 'ok'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Ошибка авторизации: ' . $e->getMessage());
            return response()->json([
                'data' => __('dictionary.MSG_MSG_LOGIN_ERROR')
            ], 500);
        }
    }
}
