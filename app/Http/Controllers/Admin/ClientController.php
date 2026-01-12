<?php

namespace App\Http\Controllers\Admin;

use App\Service\Admin;

class ClientController
{
    public function __construct()
    {
        if (!Admin::isAuth()) {
            redirect('/admin/auth.php')->send();
        }
    }

    public function buyOnline()
    {
        $data = [];
        return 'Купил билет онлайн';
        return view('admin.clients.index', $data);
    }

    public function buyCash()
    {
        $data = [];
        return 'Купил билет наличкой';
        return view('regular-races.index', $data);
    }

    public function refund()
    {
        $data = [];
        return 'Вернул билет';
        return view('regular-races.index', $data);
    }
}
