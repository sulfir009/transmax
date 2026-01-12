<?php

namespace App\Repositories\Contracts;

use App\Models\Order;

interface OrderRepositoryInterface
{
    /**
     * Создать новый заказ
     */
    public function create(array $orderData): Order;

    /**
     * Найти заказ по ID
     */
    public function findById(int $id): ?Order;

    /**
     * Обновить статус заказа
     */
    public function updateStatus(int $id, string $status): bool;

    /**
     * Получить заказы пользователя
     */
    public function getUserOrders(?int $userId): array;
}
