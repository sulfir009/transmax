<?php

namespace App\Repository\Interfaces;

interface TicketRepositoryInterface
{
    /**
     * Установить язык для запросов
     */
    public function setLanguage(string $lang): self;
    
    /**
     * Получить цены билетов для фильтра
     */
    public function getTicketPrices(int $filterDeparture = 0, int $filterArrival = 0): array;
    
    /**
     * Получить доступные дни для рекомендаций
     */
    public function getAvailableDays(int $filterDeparture, int $filterArrival): array;
    
    /**
     * Получить остановки для билета
     */
    public function getTicketStops(int $tourId): array;
    
    /**
     * Получить детали станции отправления
     */
    public function getDepartureDetails(int $tourId, int $tourDeparture): ?array;
    
    /**
     * Получить детали станции прибытия
     */
    public function getArrivalDetails(int $tourId, int $tourArrival): ?array;
    
    /**
     * Получить цену билета
     */
    public function getTicketPrice(int $tourId, int $fromStop, int $toStop): ?float;
    
    /**
     * Подсчет билетов для пагинации
     */
    public function countTickets(array $filters = []): int;
}