<?php

namespace App\Services\Home;

use App\Helpers\LocaleHelper;
use App\Helpers\TicketUrlHelper;
use App\Repository\Home\HomeContentRepository;
use App\Repository\Races\ToursRepository;
use App\Repository\CityRepository;
use App\Repository\Site\TranslationRepository;
use App\Repository\Site\ImageRepository;

class HomePageService
{
    private HomeContentRepository $contentRepository;
    private ToursRepository $toursRepository;
    private CityRepository $cityRepository;
    private TranslationRepository $translationRepository;
    private ImageRepository $imageRepository;

    public function __construct(
        HomeContentRepository $contentRepository,
        ToursRepository $toursRepository,
        CityRepository $cityRepository,
        TranslationRepository $translationRepository,
        ImageRepository $imageRepository
    ) {
        $this->contentRepository = $contentRepository;
        $this->toursRepository = $toursRepository;
        $this->cityRepository = $cityRepository;
        $this->translationRepository = $translationRepository;
        $this->imageRepository = $imageRepository;
    }

    public function getHomePageData(string $lang = 'uk'): array
    {
        // Получаем данные из сессии или используем значения по умолчанию
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Используем session() функцию Laravel для работы с сессией
        $filterDeparture = session('filter.departure', 0);
        $filterArrival = session('filter.arrival', 0);
        $filterDate = session('filter.date', date('Y-m-d'));
        $filterAdults = session('filter.adults', 1);
        $filterKids = session('filter.kids', 0);
        
        return [
            'mainBanner' => $this->contentRepository->getMainBanner($lang),
            'advantages' => $this->contentRepository->getAdvantages($lang),
            'welcomeInfo' => $this->contentRepository->getWelcomeInfo($lang),
            'countries' => $this->getCountriesForHome($lang),
            'homeCountries' => $this->getFixedCountriesForHome($lang),
            'cities' => $this->getCitiesForHome($lang),
            'internationalTours' => $this->getInternationalTours($lang),
            'homeTours' => $this->getHomeTours($lang),
            'numbersInfo' => $this->contentRepository->getNumbersInfo($lang),
            'whyWeData' => $this->contentRepository->getWhyWeData($lang),
            'reviews' => $this->contentRepository->getReviews($lang),
            'dictionary' => $this->translationRepository->getDictionary($lang),
            'site_settings' => $this->getSiteSettings(),
            'logo' => $this->imageRepository->getLogo(),
            'lang' => $lang,
            'page_data' => [
                'lang' => $lang,
                'title' => 'MaxTrans - Автобусні перевезення',
                'description' => 'Міжнародні та внутрішні автобусні перевезення'
            ],
            // Добавляем параметры для фильтра
            'filterDeparture' => $filterDeparture,
            'filterArrival' => $filterArrival,
            'filterDate' => $filterDate,
            'filterAdults' => $filterAdults,
            'filterKids' => $filterKids,
            'formAction' => TicketUrlHelper::make(
                $filterDeparture ?: null,
                $filterArrival ?: null,
                [],
                $lang
            ) 
        ];
    }

    /**
     * Получить фиксированный список стран для блока на главной странице.
     */
    public function getFixedCountriesForHome(string $lang): array
    {
        $countriesConfig = [
            ['en' => 'Ukraine', 'key' => 'MSG_ALL_COUNTRY_UKRAINE'],
            ['en' => 'Moldova', 'key' => 'MSG_ALL_COUNTRY_MOLDOVA'],
            ['en' => 'Romania', 'key' => 'MSG_ALL_COUNTRY_ROMANIA'],
            ['en' => 'Bulgaria', 'key' => 'MSG_ALL_COUNTRY_BULGARIA'],
            ['en' => 'Greece', 'key' => 'MSG_ALL_COUNTRY_GREECE'],
        ];

        $countryIdsByTitle = $this->cityRepository->getCountryIdsByEnglishTitles(
            array_column($countriesConfig, 'en')
        );

        return array_values(array_filter(array_map(function (array $country) use ($countryIdsByTitle) {
            $countryId = $countryIdsByTitle[$country['en']] ?? null;

            if ($countryId === null) {
                return null;
            }

            return [
                'id' => $countryId,
                'title' => __('dictionary.' . $country['key']),
            ];
        }, $countriesConfig)));
    }

    private function getCountriesForHome(string $lang): array
    {
        return $this->cityRepository->getCountriesForHome($lang);
    }

    private function getCitiesForHome(string $lang): array
    {
        return $this->cityRepository->getCitiesForHome($lang);
    }

    private function getInternationalTours(string $lang): array
    {
        $tours = $this->toursRepository->getInternationalTours($lang);
        return $this->removeDuplicateRoutes($tours);
    }

    private function getHomeTours(string $lang): array
    {
        $tours = $this->toursRepository->getHomeTours($lang);
        return $this->removeDuplicateRoutes($tours);
    }

    private function removeDuplicateRoutes(array $tours): array
    {
        $uniqueRoutes = [];
        $printedRoutes = [];
        
        foreach ($tours as $tour) {
            $routeKey = $tour['departure_city_id'] . '_' . $tour['arrival_city_id'];
            if (!in_array($routeKey, $printedRoutes)) {
                $uniqueRoutes[] = $tour;
                $printedRoutes[] = $routeKey;
            }
        }
        
        return $uniqueRoutes;
    }

    private function getSiteSettings(): array
    {
        // Загрузка настроек сайта из БД или конфигурации
        return [
            'BLABLACAR' => config('site.blablacar_url', 'https://www.blablacar.com.ua/')
        ];
    }
}
