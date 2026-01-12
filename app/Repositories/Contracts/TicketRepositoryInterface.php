<?php

namespace App\Repositories\Contracts;

interface TicketRepositoryInterface
{
    /**
     * Получить информацию о билете по данным заказа
     */
    public function getTicketInfo(array $orderData, string $lang = 'ru'): ?array;

    /**
     * Получить опции автобуса по ID
     */
    public function getBusOptions(int $busId, string $lang = 'ru'): array;

    /**
     * Получить информацию о месяце по номеру
     */
    public function getMonthInfo(int $monthNumber, string $lang = 'ru'): ?array;

    /**
     * Вычислить общую стоимость
     */
    public function calculateTotalPrice(array $orderData): float;
}
