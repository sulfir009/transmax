<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    private LocalizationService $localization;

    public function __construct(LocalizationService $localization)
    {
        $this->localization = $localization;
    }

    /**
     * Отправить email с подтверждением заказа
     */
    public function sendOrderConfirmation(Order $order, array $ticketInfo): bool
    {
        try {
            $emailData = [
                'order' => $order,
                'ticketInfo' => $ticketInfo,
                'lang' => $this->localization->getCurrentLang()
            ];

            // Здесь можно использовать Laravel Mail
            // Пока что логируем
            Log::info('Order confirmation email should be sent', [
                'order_id' => $order->id,
                'email' => $order->email,
                'ticket_info' => $ticketInfo
            ]);

            // В реальном приложении:
            // Mail::to($order->email)->send(new OrderConfirmationMail($emailData));

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send order confirmation email', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Отправить SMS уведомление
     */
    public function sendSmsNotification(Order $order, string $message): bool
    {
        try {
            // Здесь должна быть интеграция с SMS сервисом
            Log::info('SMS notification should be sent', [
                'order_id' => $order->id,
                'phone' => $order->phone,
                'message' => $message
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send SMS notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
