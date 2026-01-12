<?php

namespace App\Repository\Interfaces;

interface CityRepositoryInterface
{
    /**
     * Установить язык для запросов
     */
    public function setLanguage(string $lang): self;
    
    /**
     * Получить название города по ID
     */
    public function getCityTitle(int $cityId): ?array;
    
    /**
     * Получить все месяцы
     */
    public function getMonths(): array;
    
    /**
     * Получить название месяца по ID
     */
    public function getMonthTitle(int $monthId): ?array;
}