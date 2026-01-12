<?php

namespace App\Http\Controllers;

use App\Service\About\AboutService;
use App\Service\Site;
use Illuminate\Http\Request;

class AboutController extends Controller
{
    public function __construct(
        private AboutService $aboutService
    ) {
    }

    /**
     * Отображение страницы "О нас"
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $lang = Site::lang();

        // Получаем все данные для страницы через сервис
        $data = $this->aboutService->getPageData($lang);
        $data['lang'] = $lang;

        return view('about.index', $data);
    }
}
