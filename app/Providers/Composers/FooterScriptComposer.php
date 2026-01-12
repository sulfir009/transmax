<?php

namespace App\Providers\Composers;

use App\Repository\Races\CityRepository;
use App\Repository\Site\ImageRepository;
use App\Repository\Site\PhoneCodesRepository;
use App\Repository\Site\TxtBlocksRepository;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use PhpParser\Node\Expr\Empty_;

class FooterScriptComposer
{
    public function __construct(
        private ImageRepository $ImageRepository,
        private TxtBlocksRepository $txtBlocksRepository,
        private CityRepository $cityRepository,
        private PhoneCodesRepository $codesRepository
    )
    {

    }
    public function compose(View $view): void
    {
        $lang = Session::get('lang', 'ru');
        $logo = $this->ImageRepository->getLogo();
        $footerTxt = $this->txtBlocksRepository->getTextById(4);
        $footerCookie = $this->txtBlocksRepository->getTextById(5);
        $cities = $this->cityRepository->getCities();
        $phoneCodes = $this->codesRepository->getAll();
        $firstPhoneExample = $phoneCodes->first()->phone_example;
        $firstPhoneMask = $phoneCodes->first()->phone_mask;
        $filterDeparture = $_SESSION['filter']['departure'] ?? '';
        $view
            ->with('out', $this->out())
            ->with('footerTxt', $footerTxt->text)
            ->with('footerCookie', $footerCookie->text)
            ->with('cities', $cities)
            ->with('phoneCodes', $phoneCodes)
            ->with('firstPhoneExample', $firstPhoneExample)
            ->with('firstPhoneMask', $firstPhoneMask)
            ->with('filterDeparture', $filterDeparture)
            ->with('logo', $logo)
            ->with('lang', $lang)
        ;
    }

    public function out()
    {
        return function ($Elem) {
            if(!empty($_SESSION['admin'])){
                ?><pre style="font-family: Consolas,Courier;font-size: 12px;background: #1f2d3d;padding: 4px 6px;color: #17a2b8;display: inline-block;border-radius: 3px;text-align: left;margin: 3px;line-height: 1.0;"><?
                print_r($Elem);
                ?></pre><?
            }
        };
    }
}
