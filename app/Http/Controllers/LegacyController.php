<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class LegacyController
{
    public function index()
    {
       /* try {*/
            ob_start();
            require app_path('Http') . '/legacy.php';
            $output = ob_get_clean();
            return new Response($output);
        /*} catch (\Throwable $e) {
            return response("Ошибка: " . $e->getMessage(), 500);
        }*/
    }

    public function admin()
    {
        ob_start();
        require app_path('Http') . '/legacyAdmin.php';
        $output = ob_get_clean();
        // не забудьте импортировать Illuminate\Http\Response
        return new Response($output);
    }
}
