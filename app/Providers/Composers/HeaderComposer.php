<?php

namespace App\Providers\Composers;

use App\Repository\Races\RegularRacesRepository;
use App\Repository\Site\ImageRepository;
use App\Repository\Site\LanguagesRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

readonly class HeaderComposer
{
    public function __construct(
        private ImageRepository $ImageRepository,
        private RegularRacesRepository $regularRacesRepository,
        private LanguagesRepository $languagesRepository,
    ){}

    public function compose(View $view): void
    {
        $logo = $this->ImageRepository->getLogo();
        $siteLangs = $this->languagesRepository->getSiteLangs();
        $pageData = [
            'page_id' => Route::is('main') ? 1 : 0,
            'page_title' => Route::currentRouteName()
        ];

        $class = 'header';
        $isMain = false;
        $header_class = 'header-white';
        if (Route::is('main')) {
            $class .= ' index_header';
            $isMain = true;
            $header_class = 'header index_header';
        } elseif (Route::is('regular_races') ) {
            $class = 'header_blue index_header_table';
        } elseif (Route::is('thanks')) {
            $header_class = 'header_blue';
        }
       // dd(Route::currentRouteName());
        $image_logo = ($isMain) ? 'logo-light-new.png' : 'logo-light-mobile-sec.png';
        $mob_image_logo = ($isMain) ? 'logo-light-mobile.png' : 'logo-lite-sec.png';
        $mob_image_logo = $image_logo;
        $links_class = ($isMain) ? 'link' : 'link-dark';
        $lang_select_class = ($isMain) ? 'light-lang' : 'dark-lang';
        $contact_logo = ($isMain) ? 'service.svg' : 'contact-new-logo-dark.png';
        $burger_img = ($isMain) ? 'menu.svg' : 'burger-new-dark.png';
        $cabinet_logo = ($isMain) ? 'profile.svg' : 'cabinet-new-logo-dark.png';

        $images = $this->getImages($logo, $pageData);
        $regularRaces = $this->regularRacesRepository->getTitle();
        //dd(session()->get('site.last_lang'));
        $view->with('image_logo', $images['image_logo'])
            ->with('burger', $images['burger'])
            ->with('langs_class', $images['langs_class'])
            ->with('regularRaces', $regularRaces)
            ->with('siteLangs', $siteLangs)
            ->with('arrowDown', $this->getArrayDownSvg())
            ->with('header_class', $header_class)
            ->with('image_logo', $image_logo)
            ->with('mob_image_logo', $mob_image_logo)
            ->with('links_class', $links_class)
            ->with('lang_select_class', $lang_select_class)
            ->with('contact_logo', $contact_logo)
            ->with('burger_img', $burger_img)
            ->with('cabinet_logo', $cabinet_logo)
            ->with('class', $class)
            ->with('privateLink', \App\Service\User::isAuth() ? route('future_races') : route('auth'));
    }

    private function getImages(array $logo, array $pageData): array
    {
        $images = [
            'image_logo' => $logo['black_logo'],
            'burger' => 'burger_dark.svg',
            'langs_class' => 'dark',
        ];

        if($pageData['page_id'] == '1'){
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
