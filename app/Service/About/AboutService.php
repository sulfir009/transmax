<?php

namespace App\Service\About;

use App\Repository\About\AboutContentRepository;

class AboutService
{
    public function __construct(
        private AboutContentRepository $aboutContentRepository
    ) {
    }

    /**
     * Получить все данные для страницы "О нас"
     * 
     * @param string $lang
     * @return array
     */
    public function getPageData(string $lang): array
    {
        return [
            'welcomeInfo' => $this->aboutContentRepository->getWelcomeInfo($lang),
            'advantages' => $this->prepareAdvantages($this->aboutContentRepository->getAdvantages($lang)),
            'aboutUsInfo' => $this->aboutContentRepository->getAboutUsInfo($lang),
            'companyDocs' => $this->aboutContentRepository->getCompanyDocs(),
        ];
    }

    /**
     * Подготовить данные преимуществ для отображения
     * 
     * @param array $advantages
     * @return array
     */
    private function prepareAdvantages(array $advantages): array
    {
        // Здесь можно добавить дополнительную обработку данных при необходимости
        // Например, форматирование текста, обработка изображений и т.д.
        
        return $advantages;
    }

    /**
     * Форматировать текст с разделителями
     * 
     * @param string $text
     * @param string $separator
     * @return array
     */
    public function formatTextWithSeparator(string $text, string $separator = '#'): array
    {
        return explode($separator, $text);
    }
}
