<?php

namespace App\Providers\Composers;

use App\Repository\Races\RegularRacesRepository;
use App\Repository\Site\ImageRepository;
use App\Repository\Site\LanguagesRepository;
use App\Service\DbRouter\Router;
use App\Service\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

readonly class HeadComposer
{
    public function __construct(
        private ImageRepository $ImageRepository,
        private RegularRacesRepository $regularRacesRepository,
        private LanguagesRepository $languagesRepository,
    ){}


    public function compose(View $view): void
    {
        if (Route::is('tickets')){
            unset($_SESSION['filter']);
        }

        $siteLangs = $this->languagesRepository->getSiteLangs();
        $pageData = [
            'page_id' => Route::is('main') ? 1 : 0,
            'page_title' => __(Route::currentRouteName())
        ];
        $lang = Session::get('lang', 'ru');
        $logo = $this->ImageRepository->getLogo();
        $router = new Router();
        $privateLink = User::isAuth() ? $router->writelink(79) : $router->writelink(77);



        $class = 'header';
        if (Route::is('main')) {
            $class .= ' index_header';
        } elseif (Route::is('regular_races')) {
            $class = 'header_blue index_header_table';
        }

        $images = $this->getImages($logo, $pageData);
        $regularRaces = $this->regularRacesRepository->getAllAlias();

        $view->with('image_logo', $images['image_logo'])
            ->with('burger', $images['burger'])
            ->with('langs_class', $images['langs_class'])
            ->with('regularRaces', $regularRaces)
            ->with('siteLangs', $siteLangs)
            ->with('arrowDown', $this->getArrayDownSvg())
            ->with('class', $class)
            ->with('lang', $lang)
            ->with('Router', $router)
            ->with('privateLink', $privateLink)
            ->with('logo', $logo)
            ->with('pageData', $pageData)
        ;
    }

    private function getImages(array $logo, array $pageData): array
    {
        $images = [
            'image_logo' => $logo['black_logo'],
            'burger' => 'burger_dark.svg',
            'langs_class' => 'dark',
        ];

        if(Route::is('main')){
            $images = [
                'image_logo' => $logo['white_logo'],
                'burger' => 'burger.svg',
                'langs_class' => '',
            ];
        }

        return $images;
    }

    private function getArrayDownSvg()
    {
        return '<svg width="17" height="9" viewBox="0 0 17 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1.15332 0.567871L8.65333 8.06786L16.1533 0.567871" stroke="white"/>
                </svg>';
    }
}
