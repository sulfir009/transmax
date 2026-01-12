<?php

namespace App\Services\About;

use App\Repository\About\AboutContentRepository;
use App\Repository\Site\TranslationRepository;
use App\Repository\Site\ImageRepository;

class AboutPageService
{
    private AboutContentRepository $contentRepository;
    private TranslationRepository $translationRepository;
    private ImageRepository $imageRepository;

    public function __construct(
        AboutContentRepository $contentRepository,
        TranslationRepository $translationRepository,
        ImageRepository $imageRepository
    ) {
        $this->contentRepository = $contentRepository;
        $this->translationRepository = $translationRepository;
        $this->imageRepository = $imageRepository;
    }

    /**
     * Получить все данные для страницы "О нас"
     */
    public function getAboutPageData(string $lang = 'uk'): array
    {
        return [
            'welcome' => $this->contentRepository->getWelcomeInfo($lang),
            'advantages' => $this->contentRepository->getAdvantages($lang),
            'aboutUs' => $this->contentRepository->getAboutUsInfo($lang),
            'companyDocs' => $this->contentRepository->getCompanyDocs(),
            'dictionary' => $this->translationRepository->getDictionary($lang),
            'site_settings' => $this->getSiteSettings(),
            'logo' => $this->imageRepository->getLogo(),
            'lang' => $lang,
            'page_data' => [
                'lang' => $lang,
                'title' => $this->getPageTitle($lang),
                'description' => $this->getPageDescription($lang),
                'keywords' => $this->getPageKeywords($lang)
            ]
        ];
    }

    /**
     * Получить настройки сайта
     */
    private function getSiteSettings(): array
    {
        return [
            'BLABLACAR' => config('site.blablacar_url', 'https://www.blablacar.com.ua/')
        ];
    }

    /**
     * Получить заголовок страницы
     */
    private function getPageTitle(string $lang): string
    {
        $titles = [
            'uk' => 'Про нас - MaxTrans | Автобусні перевезення',
            'ru' => 'О нас - MaxTrans | Автобусные перевозки',
            'en' => 'About Us - MaxTrans | Bus Transportation'
        ];

        return $titles[$lang] ?? $titles['uk'];
    }

    /**
     * Получить описание страницы
     */
    private function getPageDescription(string $lang): string
    {
        $descriptions = [
            'uk' => 'MaxTrans - надійний перевізник з багаторічним досвідом. Міжнародні та внутрішні автобусні перевезення, комфортні автобуси, професійні водії.',
            'ru' => 'MaxTrans - надежный перевозчик с многолетним опытом. Международные и внутренние автобусные перевозки, комфортные автобусы, профессиональные водители.',
            'en' => 'MaxTrans - reliable carrier with years of experience. International and domestic bus transportation, comfortable buses, professional drivers.'
        ];

        return $descriptions[$lang] ?? $descriptions['uk'];
    }

    /**
     * Получить ключевые слова страницы
     */
    private function getPageKeywords(string $lang): string
    {
        $keywords = [
            'uk' => 'про компанію, MaxTrans, автобусні перевезення, перевізник, про нас',
            'ru' => 'о компании, MaxTrans, автобусные перевозки, перевозчик, о нас',
            'en' => 'about company, MaxTrans, bus transportation, carrier, about us'
        ];

        return $keywords[$lang] ?? $keywords['uk'];
    }
}
