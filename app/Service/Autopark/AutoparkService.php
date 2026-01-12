<?php

namespace App\Service\Autopark;

use App\Repository\Autopark\AutoparkRepository;

class AutoparkService
{
    public function __construct(
        private AutoparkRepository $autoparkRepository
    ) {
    }

    /**
     * Получить все данные для страницы автопарка
     * 
     * @param string $lang
     * @return array
     */
    public function getPageData(string $lang): array
    {
        // Получаем текстовые блоки
        $textBlocks = $this->autoparkRepository->getPageTextBlocks($lang);
        
        // Получаем автобусы
        $buses = $this->autoparkRepository->getBuses($lang);
        
        // Получаем общее количество автобусов
        $totalBusesCount = $this->autoparkRepository->getActiveBusesCount();
        
        // Получаем правила бронирования
        $bookingRules = $this->autoparkRepository->getBookingRules($lang);
        
        // Получаем коды телефонов
        $phoneCodes = $this->autoparkRepository->getPhoneCodes();
        
        return [
            'pageTitle' => $textBlocks['title'] ?? '',
            'pageSubtitle' => $textBlocks['text'] ?? '',
            'buses' => $this->prepareBusesData($buses),
            'totalBusesCount' => $totalBusesCount,
            'showMoreButton' => $totalBusesCount > 6,
            'bookingRules' => $bookingRules,
            'phoneCodes' => $this->preparePhoneCodes($phoneCodes),
        ];
    }

    /**
     * Подготовить данные автобусов для отображения
     * 
     * @param array $buses
     * @return array
     */
    private function prepareBusesData(array $buses): array
    {
        return array_map(function ($bus) {
            // Обработка опций - группировка по 3 для правильного отображения
            $bus['options_grouped'] = array_chunk($bus['options'] ?? [], 3);
            
            // Обеспечиваем наличие массива изображений
            $bus['images'] = $bus['images'] ?? [];
            
            return $bus;
        }, $buses);
    }

    /**
     * Подготовить коды телефонов
     * 
     * @param array $phoneCodes
     * @return array
     */
    private function preparePhoneCodes(array $phoneCodes): array
    {
        $prepared = [
            'default' => [],
            'codes' => []
        ];
        
        foreach ($phoneCodes as $index => $phoneCode) {
            $code = (array) $phoneCode;
            
            // Первый элемент - дефолтный
            if ($index === 0) {
                $prepared['default'] = [
                    'mask' => $code['phone_mask'] ?? '',
                    'example' => $code['phone_example'] ?? '',
                ];
            }
            
            $prepared['codes'][] = $code;
        }
        
        // Если нет кодов, устанавливаем дефолтные значения
        if (empty($prepared['codes'])) {
            $prepared['default'] = [
                'mask' => '(999) 999-9999',
                'example' => '(999) 999-9999',
            ];
        }
        
        return $prepared;
    }

    /**
     * Получить дополнительные автобусы для AJAX подгрузки
     * 
     * @param string $lang
     * @param int $currentCount
     * @return array
     */
    public function getMoreBuses(string $lang, int $currentCount): array
    {
        $buses = $this->autoparkRepository->getMoreBuses($lang, $currentCount);
        return $this->prepareBusesData($buses);
    }

    /**
     * Обработать заказ автобуса
     * 
     * @param array $data
     * @return bool
     */
    public function processOrderBus(array $data): bool
    {
        // Здесь должна быть логика обработки заказа автобуса
        // Например, сохранение в БД, отправка email и т.д.
        
        // Временно возвращаем true для демонстрации
        // В реальном проекте здесь будет полноценная логика
        
        return true;
    }
}
