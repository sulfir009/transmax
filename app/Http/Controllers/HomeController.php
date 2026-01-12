<?php

namespace App\Http\Controllers;

use App\Service\Site;
use App\Services\Home\HomePageService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    private HomePageService $homePageService;

    public function __construct(HomePageService $homePageService)
    {
        $this->homePageService = $homePageService;
    }

    public function index(Request $request)
    {
        $request->validate([
            'lang' => 'nullable|in:uk,ru,en'
        ]);

        $lang = Site::lang();
        $data = $this->homePageService->getHomePageData($lang);

        return view('pages.home', $data);
    }
}
