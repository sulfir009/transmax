<?php

namespace App\Providers\Composers;

use App\Repository\Site\ImageRepository;
use App\Service\DbRouter\Router;
use App\Service\Site;
use App\Service\User;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class LayoutAppComposer
{
    public function __construct(private ImageRepository $ImageRepository)
    {

    }
    public function compose(View $view): void
    {
        $lang = Site::lang();
        $logo = $this->ImageRepository->getLogo();
        $router = new Router();
        $privateLink = User::isAuth() ? $router->writelink(79) : $router->writelink(77);

        $view->with('lang', $lang)
            ->with('Router', $router)
            ->with('privateLink', $privateLink)
            ->with('logo', $logo);
    }
}
