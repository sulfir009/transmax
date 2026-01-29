<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Service\TicketService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PaymentFinalizer
{
    public static function buildCorrelationId(?int $orderId, ?string $uniqid, ?string $invoiceId): string
    {
        $parts = [
            $orderId !== null ? (string) $orderId : 'unknown',
            $uniqid ?: 'unknown',
            $invoiceId ?: 'unknown',
        ];

        return implode('|', $parts);
    }

    public function finalize(Order $order, array $payload, string $source): array
    {
        $legacyOrderId = (string) ($order->uniqid ?: ($order->uniqId ?? null) ?: ('ORDER_' . $order->id));
        $invoiceId = $payload['invoiceId'] ?? $order->mono_invoice_id ?? null;
        $correlationId = self::buildCorrelationId($order->id, $legacyOrderId, $invoiceId);

        $ticketFiles = $this->findTicketFiles($order->id);
        $alreadyFinalized = count($ticketFiles) > 0;
        $paymentStatus = (int) ($order->payment_status ?? 0);

        Log::info('[PaymentFinalizer] start', [
            'correlation_id' => $correlationId,
            'source' => $source,
            'order_db_id' => $order->id,
            'legacy_order_id' => $legacyOrderId,
            'invoice_id' => $invoiceId,
            'payment_status' => $paymentStatus,
            'tickets_found' => count($ticketFiles),
        ]);

        Log::info('[PaymentFinalizer] step:update_status', [
            'correlation_id' => $correlationId,
            'status' => $paymentStatus,
            'note' => $paymentStatus === 2 ? 'already_paid' : 'not_paid',
        ]);

        if ($paymentStatus !== 2) {
            Log::warning('[PaymentFinalizer] skip finalize: payment not marked as paid', [
                'correlation_id' => $correlationId,
                'order_db_id' => $order->id,
                'legacy_order_id' => $legacyOrderId,
                'payment_status' => $paymentStatus,
            ]);

            return [
                'status' => 'skipped',
                'reason' => 'payment_not_paid',
                'correlation_id' => $correlationId,
            ];
        }

        if ($alreadyFinalized) {
            Log::info('[PaymentFinalizer] step:generate_ticket skipped', [
                'correlation_id' => $correlationId,
                'reason' => 'tickets_already_generated',
                'ticket_files' => $ticketFiles,
            ]);

            Log::info('[PaymentFinalizer] step:send_email skipped', [
                'correlation_id' => $correlationId,
                'reason' => 'tickets_already_generated',
            ]);

            Log::info('[PaymentFinalizer] step:write_admin_online skipped', [
                'correlation_id' => $correlationId,
                'reason' => 'tickets_already_generated',
            ]);

            return [
                'status' => 'skipped',
                'reason' => 'already_finalized',
                'correlation_id' => $correlationId,
            ];
        }

        Cache::put($this->finalizeAttemptCacheKey($order->id), [
            'started_at' => now()->toIso8601String(),
            'source' => $source,
            'correlation_id' => $correlationId,
        ], now()->addDay());

        try {
            Log::info('[PaymentFinalizer] step:generate_ticket', [
                'correlation_id' => $correlationId,
                'note' => 'delegating_to_ticket_service',
            ]);

            /** @var TicketService $ticketService */
            $ticketService = app(TicketService::class);
            $ticketService->processSuccessfulPayment($legacyOrderId, array_merge($payload, [
                'source' => $source,
                'correlation_id' => $correlationId,
            ]));

            Log::info('[PaymentFinalizer] step:send_email', [
                'correlation_id' => $correlationId,
                'note' => 'delegating_to_ticket_service',
            ]);

            Log::info('[PaymentFinalizer] step:write_admin_online', [
                'correlation_id' => $correlationId,
                'note' => 'delegating_to_ticket_service',
            ]);

            Log::info('[PaymentFinalizer] completed', [
                'correlation_id' => $correlationId,
                'order_db_id' => $order->id,
                'legacy_order_id' => $legacyOrderId,
            ]);

            return [
                'status' => 'ok',
                'correlation_id' => $correlationId,
            ];
        } catch (\Throwable $e) {
            Log::error('[PaymentFinalizer] failed', [
                'correlation_id' => $correlationId,
                'order_db_id' => $order->id,
                'legacy_order_id' => $legacyOrderId,
                'error' => $e->getMessage(),
            ]);

            Cache::put($this->finalizeErrorCacheKey($order->id), [
                'error' => $e->getMessage(),
                'failed_at' => now()->toIso8601String(),
                'source' => $source,
                'correlation_id' => $correlationId,
            ], now()->addDay());

            return [
                'status' => 'error',
                'reason' => 'finalization_failed',
                'correlation_id' => $correlationId,
            ];
        }
    }

    private function findTicketFiles(int $orderId): array
    {
        $pattern = storage_path('app/tickets/ticket_' . $orderId . '*.pdf');
        return glob($pattern) ?: [];
    }

    private function finalizeErrorCacheKey(int $orderId): string
    {
        return 'payment_debug:last_finalize_error:' . $orderId;
    }

    private function finalizeAttemptCacheKey(int $orderId): string
    {
        return 'payment_debug:last_finalize_attempt:' . $orderId;
    }
}
