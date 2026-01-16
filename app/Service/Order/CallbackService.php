<?php

namespace App\Service\Order;

use App\Service\LiqPayService;
use App\Service\TicketService;
use Illuminate\Support\Facades\Log;

class CallbackService
{
    protected LiqPayService $liqPayService;
    protected TicketService $ticketService;

    public function __construct(LiqPayService $liqPayService, TicketService $ticketService)
    {
        $this->liqPayService = $liqPayService;
        $this->ticketService = $ticketService;
    }

    /**
     * Обработка ответа от LiqPay
     */
    public function handle(array $inputData): bool
    {
        $data = $inputData['data'] ?? null;
        $signature = $inputData['signature'] ?? null;

        if (!$data || !$signature) {
            Log::channel('payment')->error('CallbackService: Отсутствуют data или signature');
            return false;
        }

        $decodedData = $this->liqPayService->processCallback($data, $signature);

        if (!$decodedData) {
            Log::channel('payment')->error('CallbackService: Невалидная подпись');
            return false;
        }

        $status = $decodedData['status'] ?? 'unknown';
        $orderId = $decodedData['order_id'] ?? null;

        Log::channel('payment')->info("CallbackService: Получен callback для заказа {$orderId} со статусом {$status}");

        if ($status === 'success' || $status === 'sandbox') {
            Log::channel('payment')->info("CallbackService: Платеж успешен. Запускаем TicketService...");
            return $this->ticketService->processSuccessfulPayment($orderId, $decodedData);
        }

        Log::channel('payment')->info("CallbackService: Платеж не успешен (статус {$status}), действия не требуются.");

        return true;
    }
}