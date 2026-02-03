<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Service\TicketService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MonobankWebhookHandler
{
    private PaymentFinalizer $finalizer;

    public function __construct(PaymentFinalizer $finalizer)
    {
        $this->finalizer = $finalizer;
    }

    public function process(array $data, string $source, array $context = []): array
    {
        $invoiceId = $data['invoiceId'] ?? null;
        $statusRaw = $data['status'] ?? null;
        $status = strtolower((string) $statusRaw);

        $correlationId = $context['correlation_id'] ?? PaymentFinalizer::buildCorrelationId(null, null, $invoiceId);

        if (!$invoiceId) {
            Log::warning('[Monobank] webhook missing invoiceId', [
                'correlation_id' => $correlationId,
                'source' => $source,
            ]);

            return [
                'status' => 'ignored',
                'reason' => 'missing_invoice_id',
                'correlation_id' => $correlationId,
            ];
        }

        $needFinalize = false;
        $matchedOrder = null;
        $legacyOrderId = null;
        $staleWebhook = false;
        $incomingModifiedAt = $this->extractModifiedAt($data);

        DB::transaction(function () use (
            $invoiceId,
            $status,
            $data,
            &$needFinalize,
            &$matchedOrder,
            &$legacyOrderId,
            &$correlationId,
            &$staleWebhook,
            $incomingModifiedAt
        ) {
            /** @var Order|null $order */
            $order = Order::where('mono_invoice_id', $invoiceId)->lockForUpdate()->first();

            if (!$order) {
                $ref = $data['reference'] ?? ($data['merchantPaymInfo']['reference'] ?? null);
                if ($ref) {
                    $order = Order::where('uniqId', (string) $ref)
                        ->orWhere('uniqid', (string) $ref)
                        ->lockForUpdate()
                        ->first();
                }
            }

            if (!$order) {
                Log::error('[Monobank] webhook: order mapping not found', [
                    'correlation_id' => $correlationId,
                    'invoiceId' => $invoiceId,
                    'status' => $status,
                ]);

                Payment::updateOrCreate(
                    ['payment_id' => (string) $invoiceId],
                    [
                        'user_id' => null,
                        'order_id' => 'UNKNOWN_' . (string) $invoiceId,
                        'status' => $status ?: 'unknown',
                        'amount' => null,
                        'currency' => 'UAH',
                        'description' => "Monobank invoice #{$invoiceId}",
                        'response' => json_encode($data, JSON_UNESCAPED_UNICODE),
                        'paid_at' => in_array($status, ['success', 'paid'], true) ? now() : null,
                    ]
                );

                return;
            }

            $legacyOrderId = (string) ($order->uniqid ?: ($order->uniqId ?? null) ?: ('ORDER_' . $order->id));
            $correlationId = PaymentFinalizer::buildCorrelationId($order->id, $legacyOrderId, $invoiceId);
            $matchedOrder = $order;

            if ($incomingModifiedAt && $order->mono_modified_at) {
                $storedTimestamp = Carbon::parse($order->mono_modified_at)->getTimestamp();
                $incomingTimestamp = $incomingModifiedAt->getTimestamp();

                if ($incomingTimestamp <= $storedTimestamp) {
                    Log::info('[Monobank] webhook ignored (stale modifiedDate)', [
                        'correlation_id' => $correlationId,
                        'invoiceId' => $invoiceId,
                        'incoming_modified' => $incomingModifiedAt->toIso8601String(),
                        'stored_modified' => Carbon::parse($order->mono_modified_at)->toIso8601String(),
                    ]);

                    $staleWebhook = true;
                    return;
                }
            }

            Log::info('[Monobank] webhook matched order', [
                'correlation_id' => $correlationId,
                'invoiceId' => $invoiceId,
                'order_db_id' => $order->id,
                'legacy_order_id' => $legacyOrderId,
                'mono_status' => $status,
            ]);

            Cache::put($this->webhookCacheKey($order->id), [
                'invoiceId' => $invoiceId,
                'status' => $status,
                'received_at' => now()->toIso8601String(),
                'correlation_id' => $correlationId,
            ], now()->addDay());

            $alreadyPaid = ((int) ($order->payment_status ?? 0) === 2);

            $order->mono_status = $status ?: 'unknown';
            if ($incomingModifiedAt) {
                $order->mono_modified_at = $incomingModifiedAt->format('Y-m-d H:i:s');
            }

            if (in_array($status, ['success', 'paid'], true)) {
                if (!$alreadyPaid) {
                    $order->payment_status = 2;
                    $order->paid_at = $order->paid_at ?: now();
                    $needFinalize = true;
                }
            } elseif (in_array($status, ['failure', 'failed', 'expired', 'reversed', 'cancelled', 'canceled', 'declined'], true)) {
                if (!$alreadyPaid) {
                    $order->payment_status = 3;
                }
            }

            $order->save();

            $payloadJson = json_encode($data, JSON_UNESCAPED_UNICODE);

            $payment = Payment::where('order_id', $legacyOrderId)->lockForUpdate()->first();
            if (!$payment) {
                $payment = Payment::where('payment_id', (string) $invoiceId)->lockForUpdate()->first();
            }

            if ($payment) {
                $payment->order_id = $legacyOrderId;
                $payment->payment_id = (string) $invoiceId;
                $payment->status = $status ?: $payment->status;
                $payment->currency = $payment->currency ?: 'UAH';
                $payment->response = $payloadJson;

                if (in_array($status, ['success', 'paid'], true)) {
                    $payment->paid_at = $payment->paid_at ?: now();
                }

                $payment->save();
            } else {
                Payment::updateOrCreate(
                    ['order_id' => $legacyOrderId],
                    [
                        'user_id' => null,
                        'payment_id' => (string) $invoiceId,
                        'status' => $status ?: 'unknown',
                        'amount' => null,
                        'currency' => 'UAH',
                        'description' => "Monobank invoice #{$invoiceId}",
                        'response' => $payloadJson,
                        'paid_at' => in_array($status, ['success', 'paid'], true) ? now() : null,
                    ]
                );
            }
        });

        if ($staleWebhook) {
            return [
                'status' => 'ignored',
                'reason' => 'stale_modified_date',
                'correlation_id' => $correlationId,
                'invoice_id' => $invoiceId,
            ];
        }

        if (!$matchedOrder) {
            return [
                'status' => 'missing_order',
                'correlation_id' => $correlationId,
                'invoice_id' => $invoiceId,
            ];
        }

        $finalizeResult = null;
        if ($needFinalize) {
            $finalizeResult = $this->finalizer->finalize($matchedOrder, $data, $source);
        }

        if ((int) ($matchedOrder->payment_status ?? 0) === PaymentFinalizer::PAYMENT_STATUS_PAID) {
            $this->dispatchTicketsOnce($matchedOrder, (string) $legacyOrderId, $data, $correlationId);
        }

        Log::info('[Monobank] webhook processed', [
            'correlation_id' => $correlationId,
            'invoiceId' => $invoiceId,
            'order_db_id' => $matchedOrder->id,
            'legacy_order_id' => $legacyOrderId,
            'need_finalize' => $needFinalize,
            'finalize_result' => $finalizeResult,
        ]);

        return [
            'status' => 'ok',
            'correlation_id' => $correlationId,
            'order_db_id' => $matchedOrder->id,
            'legacy_order_id' => $legacyOrderId,
            'finalize_result' => $finalizeResult,
        ];
    }

    private function dispatchTicketsOnce(Order $order, string $legacyOrderId, array $paymentPayload, string $correlationId): void
    {
        $lockKey = 'tickets:sent:' . (int) $order->id;
        $ttlSeconds = 86400;

        if (!Cache::add($lockKey, 1, $ttlSeconds)) {
            Log::info('[tickets] webhook dispatch skipped (lock exists)', [
                'correlation_id' => $correlationId,
                'order_id' => (int) $order->id,
                'legacy_order_id' => $legacyOrderId,
            ]);
            return;
        }

        try {
            /** @var TicketService $ticketService */
            $ticketService = app(TicketService::class);

            Log::info('[tickets] webhook dispatch start', [
                'correlation_id' => $correlationId,
                'order_id' => (int) $order->id,
                'legacy_order_id' => $legacyOrderId,
            ]);

            $payload = array_merge($paymentPayload, [
                'status' => $paymentPayload['status'] ?? 'success',
                'payment_provider' => $paymentPayload['payment_provider'] ?? 'monobank',
                'order_id' => $legacyOrderId,
            ]);

            $ticketService->processSuccessfulPayment($legacyOrderId, $payload, $correlationId);

            Log::info('[tickets] webhook dispatch done', [
                'correlation_id' => $correlationId,
                'order_id' => (int) $order->id,
                'legacy_order_id' => $legacyOrderId,
            ]);
        } catch (Throwable $e) {
            Cache::forget($lockKey);

            Log::error('[tickets] webhook dispatch failed', [
                'correlation_id' => $correlationId,
                'order_id' => (int) $order->id,
                'legacy_order_id' => $legacyOrderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function webhookCacheKey(int $orderId): string
    {
        return 'payment_debug:last_webhook:' . $orderId;
    }

    private function extractModifiedAt(array $data): ?Carbon
    {
        $modified = $data['modifiedDate'] ?? ($data['createdDate'] ?? null);
        if (!$modified) {
            return null;
        }

        try {
            return Carbon::parse((string) $modified)->utc();
        } catch (Throwable $e) {
            return null;
        }
    }
}
