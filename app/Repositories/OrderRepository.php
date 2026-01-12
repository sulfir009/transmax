<?php

namespace App\Repositories;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Facades\Log;

class OrderRepository implements OrderRepositoryInterface
{
    /**
     * Создать новый заказ
     */
    public function create(array $orderData): Order
    {
        try {
            // Адаптируем данные под существующую структуру БД
            $adaptedData = [
                'active' => 1,
                'client_id' => $orderData['client_id'] ?? 0, // Может быть 0 для гостевых заказов
                'tour_id' => (int)$orderData['tour_id'],
                'from_stop' => (int)$orderData['from_stop'],
                'to_stop' => (int)$orderData['to_stop'],
                'tour_date' => $orderData['date'],
                'passagers' => (int)$orderData['passengers'], // Используем правильное имя поля
                'document' => $orderData['document'] ?? 1, // По умолчанию паспорт
                'date' => now(),
                'ticket_return' => 0,
                'return_reason' => 0,
                'return_payment_type' => 0,
                'return_date' => now(),
                'client_name' => $orderData['client_name'] ?? '',
                'client_surname' => $orderData['client_surname'] ?? '',
                'client_email' => $orderData['email'] ?? '',
                'client_phone' => $orderData['phone'] ?? '',
                'uniqid' => uniqid('order_', true),
                'payment_status' => $this->getPaymentStatus($orderData['paymethod'] ?? 'cash')
            ];

            $order = Order::create($adaptedData);

            Log::info('Order created successfully', ['order_id' => $order->id]);
            return $order;

        } catch (\Exception $e) {
            Log::error('Failed to create order', [
                'error' => $e->getMessage(),
                'data' => $orderData
            ]);
            throw $e;
        }
    }

    /**
     * Найти заказ по ID
     */
    public function findById(int $id): ?Order
    {
        return Order::with(['tour', 'fromStop', 'toStop', 'client'])->find($id);
    }

    /**
     * Обновить статус заказа
     */
    public function updateStatus(int $id, string $status): bool
    {
        try {
            $paymentStatus = $this->mapStatusToPaymentStatus($status);
            $updated = Order::where('id', $id)->update(['payment_status' => $paymentStatus]);
            
            if ($updated) {
                Log::info('Order status updated', ['order_id' => $id, 'status' => $status, 'payment_status' => $paymentStatus]);
            }
            
            return (bool)$updated;

        } catch (\Exception $e) {
            Log::error('Failed to update order status', [
                'order_id' => $id,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Получить заказы пользователя
     */
    public function getUserOrders(?int $userId): array
    {
        $query = Order::with(['tour', 'fromStop', 'toStop', 'client'])
                     ->where('active', 1)
                     ->orderBy('date', 'desc');

        if ($userId) {
            $query->where('client_id', $userId);
        }

        return $query->get()->toArray();
    }

    /**
     * Найти заказ по номеру тура и параметрам
     */
    public function findByTourParams(int $tourId, int $fromStop, int $toStop, string $date): ?Order
    {
        return Order::where('tour_id', $tourId)
                   ->where('from_stop', $fromStop)
                   ->where('to_stop', $toStop)
                   ->where('tour_date', $date)
                   ->where('active', 1)
                   ->orderBy('date', 'desc')
                   ->first();
    }

    /**
     * Получить статистику заказов
     */
    public function getOrderStats(): array
    {
        return [
            'total' => Order::where('active', 1)->count(),
            'pending' => Order::where('active', 1)->where('payment_status', 1)->count(),
            'completed' => Order::where('active', 1)->where('payment_status', 2)->count(),
            'cancelled' => Order::where('active', 0)->count(),
        ];
    }

    /**
     * Преобразовать метод оплаты в статус
     */
    private function getPaymentStatus(string $paymethod): int
    {
        switch ($paymethod) {
            case 'cash':
                return 1; // Ожидание оплаты
            case 'cardpay':
                return 1; // Ожидание оплаты (будет изменен после успешной оплаты)
            default:
                return 1;
        }
    }

    /**
     * Преобразовать статус в payment_status
     */
    private function mapStatusToPaymentStatus(string $status): int
    {
        switch ($status) {
            case 'pending':
                return 1;
            case 'completed':
                return 2;
            case 'failed':
                return 3;
            case 'cancelled':
                return 4;
            default:
                return 1;
        }
    }
}
