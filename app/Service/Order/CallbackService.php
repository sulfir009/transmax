<?php

namespace App\Service\Order;

use App\Service\LiqPayService;
use App\Service\TicketService;
use Illuminate\Support\Facades\Log;

class CallbackService
{
    protected LiqPayService $liqPayService;
    protected TicketService $ticketService;

    // Внедряем уже существующие сервисы
    public function __construct(LiqPayService $liqPayService, TicketService $ticketService)
    {
        $this->liqPayService = $liqPayService;
        $this->ticketService = $ticketService;
    }

    public function handle(array $inputData): bool
    {
        $data = $inputData['data'] ?? null;
        $signature = $inputData['signature'] ?? null;

        // 1. Простая проверка: пришли ли данные
        if (!$data || !$signature) {
            Log::channel('payment')->error('CallbackService: Нет данных или подписи');
            return false;
        }

        // 2. Используем родной сервис LiqPay для расшифровки (он уже есть в проекте)
        $decodedData = $this->liqPayService->processCallback($data, $signature);

        if (!$decodedData) {
            Log::channel('payment')->error('CallbackService: Ошибка проверки подписи LiqPay');
            return false;
        }

        $status = $decodedData['status'] ?? 'unknown';
        $orderId = $decodedData['order_id'] ?? null;

        Log::channel('payment')->info("CallbackService: Статус платежа '{$status}' для заказа {$orderId}");

        // 3. Если платеж успешен — вызываем TicketService
        // Именно TicketService содержит всю логику обновления базы, PDF и отправки писем.
        if ($status === 'success' || $status === 'sandbox') {
            Log::channel('payment')->info("CallbackService: Платеж успешен, передаем управление в TicketService");
            
            // ВАЖНО: Мы ничего не меняем в логике TicketService, просто запускаем его.
            return $this->ticketService->processSuccessfulPayment($orderId, $decodedData);
        }

        return true; // Платеж не успешен (отменен/ошибка), но обработан корректно
    }
}