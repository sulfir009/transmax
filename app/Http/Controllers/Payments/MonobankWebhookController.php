<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Service\TicketService as LegacyTicketService;
use App\Services\Payments\MonobankAcquiringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MonobankWebhookController extends Controller
{
    /**
     * Monobank webhook endpoint.
     *
     * Что делаем:
     * 1) Проверяем подпись (X-Sign / X-Signature).
     * 2) Находим заказ по mono_invoice_id (fallback: по reference/uniqId если пришло).
     * 3) Обновляем order->mono_status и order->payment_status (2=paid, 3=failed) идемпотентно.
     * 4) Обновляем/создаём Payment без нарушения unique по payments.order_id_unique (order_id = legacy uniqId).
     * 5) При статусе success — один раз запускаем legacy TicketService (вне транзакции).
     *
     * ВАЖНО:
     * - Финализацию (билет/почта) запускаем ТОЛЬКО из webhook.
     * - successUrl/returnUrl — это просто редиректы и не гарантируют оплату.
     */
    public function handle(Request $request, MonobankAcquiringService $mono)
    {
        $raw = (string)$request->getContent();
        $correlationId = (string) ($request->header('X-Correlation-Id') ?: Str::uuid());
        $maskedBody = mb_substr($raw, 0, 1200);
        Log::info('[Monobank] webhook received', [
            'correlation_id' => $correlationId,
            'headers' => $request->headers->all(),
            'body' => $maskedBody,
        ]);

        // Моно может прислать подпись в X-Sign (часто) или X-Signature (иногда)
        $xSign = (string)($request->header('X-Sign') ?: $request->header('X-Signature'));

        if ($raw === '') {
            // пустой вебхук — ничего не делаем
            return response('ok', 200);
        }

        // 1) Проверка подписи
        if ($xSign === '' || !$mono->verifyWebhook($raw, $xSign)) {
            Log::warning('[Monobank] webhook bad signature', [
                'correlation_id' => $correlationId,
                'ip' => $request->ip(),
                'has_x_sign' => $xSign !== '',
                'len' => strlen($raw),
            ]);
            // здесь лучше 400, чтобы ты видел проблему сразу (но моно может ретраить)
            return response('bad signature', 400);
        }

        // 2) Парсим JSON
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            Log::warning('[Monobank] webhook bad json', [
                'correlation_id' => $correlationId,
                'ip' => $request->ip(),
                'raw_preview' => mb_substr($raw, 0, 300),
            ]);
            return response('bad json', 400);
        }

        $invoiceId = $data['invoiceId'] ?? null;
        $statusRaw = $data['status'] ?? null;

        if (!$invoiceId) {
            // если нет invoiceId — нечего привязать
            return response('ok', 200);
        }

        $status = strtolower((string)$statusRaw);

        // Для финализации билета (после коммита)
        $needFinalize = false;
        $legacyOrderIdForFinalize = null;
        $orderDbIdForFinalize = null;

        try {
            DB::transaction(function () use (
                $invoiceId,
                $status,
                $data,
                &$needFinalize,
                &$legacyOrderIdForFinalize,
                &$orderDbIdForFinalize
            ) {
                /** @var Order|null $order */
                $order = Order::where('mono_invoice_id', $invoiceId)->lockForUpdate()->first();

                // Fallback: иногда удобно искать по reference (если ты будешь его отправлять/оно придет в вебхук)
                if (!$order) {
                    $ref = $data['reference'] ?? ($data['merchantPaymInfo']['reference'] ?? null);
                    if ($ref) {
                        $order = Order::where('uniqId', (string)$ref)->lockForUpdate()->first();
                    }
                }

                if (!$order) {
                    // Заказ не нашли — всё равно зафиксируем webhook в payments по payment_id, чтобы не потерять событие
                    Log::warning('[Monobank] webhook: order not found', [
                        'correlation_id' => $correlationId,
                        'invoiceId' => $invoiceId,
                        'status' => $status,
                    ]);

                    Payment::updateOrCreate(
                        ['payment_id' => (string)$invoiceId],
                        [
                            'user_id' => null,
                            'order_id' => 'UNKNOWN_' . (string)$invoiceId, // чтобы не конфликтовать с unique order_id
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

                // legacy uniqId (ключ mt_orders) — это то, что LiqPay пишет как order_id и что TicketService понимает
                // В модели у тебя аксессор getUniqidAttribute, поэтому обычно $order->uniqid уже ок.
                $legacyOrderId = (string)($order->uniqid ?: ($order->uniqId ?? null) ?: ('ORDER_' . $order->id));

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

                // Запомним, был ли уже оплачен (идемпотентность)
                $alreadyPaid = ((int)($order->payment_status ?? 0) === 2);

                // 1) Всегда сохраняем статус моно (для истории)
                $order->mono_status = $status ?: 'unknown';

                // 2) Маппинг статусов в твой payment_status (как в legacy)
                // 2 = оплачено, 3 = ошибка/неоплачено
                if (in_array($status, ['success', 'paid'], true)) {
                    // переход в paid делаем один раз
                    if (!$alreadyPaid) {
                        $order->payment_status = 2;
                        // paid_at в fillable не указан, но если колонка есть — можно писать, MySQL это примет
                        $order->paid_at = $order->paid_at ?: now();
                        $needFinalize = true;
                        $legacyOrderIdForFinalize = $legacyOrderId;
                        $orderDbIdForFinalize = $order->id;
                    }
                } elseif (in_array($status, ['failure', 'failed', 'expired', 'reversed', 'cancelled', 'canceled', 'declined'], true)) {
                    // ВАЖНО: если уже paid — не перезатирай в failed
                    if (!$alreadyPaid) {
                        $order->payment_status = 3;
                    }
                }

                $order->save();

                // 3) Payments: обновляем/создаём без дублей по unique(payments.order_id)
                // ВАЖНО: order_id здесь должен быть именно legacyOrderId (строка), а не $order->id (число)
                $payloadJson = json_encode($data, JSON_UNESCAPED_UNICODE);

                // Сначала ищем по order_id (правильная связь)
                $payment = Payment::where('order_id', $legacyOrderId)->lockForUpdate()->first();

                // Если вдруг в старых данных было иначе — fallback по payment_id
                if (!$payment) {
                    $payment = Payment::where('payment_id', (string)$invoiceId)->lockForUpdate()->first();
                }

                if ($payment) {
                    $payment->order_id = $legacyOrderId;           // фиксируем правильную связь
                    $payment->payment_id = (string)$invoiceId;     // invoiceId
                    $payment->status = $status ?: $payment->status;
                    $payment->currency = $payment->currency ?: 'UAH';
                    $payment->response = $payloadJson;

                    if (in_array($status, ['success', 'paid'], true)) {
                        $payment->paid_at = $payment->paid_at ?: now();
                    }

                    $payment->save();
                } else {
                    // Создаём безопасно через updateOrCreate по order_id (уникальный ключ)
                    Payment::updateOrCreate(
                        ['order_id' => $legacyOrderId],
                        [
                            'user_id' => null,
                            'payment_id' => (string)$invoiceId,
                            'status' => $status ?: 'unknown',
                            'amount' => null, // можно подставить сумму из заказа/цены, но webhook может приходить без неё
                            'currency' => 'UAH',
                            'description' => "Monobank invoice #{$invoiceId}",
                            'response' => $payloadJson,
                            'paid_at' => in_array($status, ['success', 'paid'], true) ? now() : null,
                        ]
                    );
                }
            });
        } catch (\Throwable $e) {
            Log::error('[Monobank] webhook handle failed', [
                'correlation_id' => $correlationId,
                'invoiceId' => $invoiceId,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);

            // ВАЖНО: моно будет ретраить, если не 200.
            // Мы возвращаем 200, чтобы не устроить бесконечные ретраи на проде.
            return response('ok', 200);
        }

        // 3) ВНЕ транзакции запускаем финализацию билета/почты (это может быть тяжёлая операция)
        if ($needFinalize && $legacyOrderIdForFinalize && $orderDbIdForFinalize) {
            try {
                /** @var LegacyTicketService $ticketService */
                $ticketService = app(LegacyTicketService::class);

                // Если у тебя сигнатура другая — скажешь, я подстрою.
                // Важно: передаём legacy uniqId (ORDER_...) и сырые данные webhook.
                Cache::put($this->finalizeAttemptCacheKey($orderDbIdForFinalize), [
                    'started_at' => now()->toIso8601String(),
                    'source' => 'monobank_webhook',
                    'correlation_id' => $correlationId,
                ], now()->addDay());
                $ticketService->processSuccessfulPayment((string)$legacyOrderIdForFinalize, $data);

                Log::info('[Monobank] ticket finalized via TicketService', [
                    'correlation_id' => $correlationId,
                    'legacy_order_id' => $legacyOrderIdForFinalize,
                    'invoiceId' => $invoiceId,
                ]);
            } catch (\Throwable $e) {
                // Оплата прошла, но билет не сгенерился — это критично, но webhook уже приняли.
                Log::error('[Monobank] ticket finalize failed', [
                    'correlation_id' => $correlationId,
                    'legacy_order_id' => $legacyOrderIdForFinalize,
                    'invoiceId' => $invoiceId,
                    'error' => $e->getMessage(),
                ]);
                Cache::put($this->finalizeErrorCacheKey($orderDbIdForFinalize), [
                    'error' => $e->getMessage(),
                    'failed_at' => now()->toIso8601String(),
                    'source' => 'monobank_webhook',
                    'correlation_id' => $correlationId,
                ], now()->addDay());
            }
        }

        return response('ok', 200);
    }

    private function webhookCacheKey(int $orderId): string
    {
        return 'payment_debug:last_webhook:' . $orderId;
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
