<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Service\TicketService as LegacyTicketService;
use App\Services\Payments\MonobankAcquiringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonobankWebhookController extends Controller
{
    public function handle(Request $request, MonobankAcquiringService $mono)
    {
        $raw   = (string)$request->getContent();
        $xSign = (string)($request->header('X-Sign') ?: $request->header('X-Signature'));

        // ✅ Лог входа: поможет понять, приходит ли webhook вообще
        Log::channel('payment')->info('[Monobank] webhook IN', [
            'ip' => $request->ip(),
            'len' => strlen($raw),
            'has_x_sign' => $xSign !== '',
            'headers_preview' => [
                'X-Sign' => $request->header('X-Sign'),
                'X-Signature' => $request->header('X-Signature'),
                'Content-Type' => $request->header('Content-Type'),
                'User-Agent' => $request->userAgent(),
            ],
            'raw_preview' => mb_substr($raw, 0, 500),
        ]);

        if ($raw === '') {
            return response('ok', 200);
        }

        // 1) Проверка подписи
        if ($xSign === '' || !$mono->verifyWebhook($raw, $xSign)) {
            Log::channel('payment')->warning('[Monobank] webhook BAD SIGNATURE', [
                'ip' => $request->ip(),
                'has_x_sign' => $xSign !== '',
                'len' => strlen($raw),
                'raw_preview' => mb_substr($raw, 0, 500),
            ]);

            // Да, моно будет ретраить, но это правильно: подпись должна быть валидна
            return response('bad signature', 400);
        }

        // 2) JSON
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            Log::channel('payment')->warning('[Monobank] webhook BAD JSON', [
                'ip' => $request->ip(),
                'raw_preview' => mb_substr($raw, 0, 800),
            ]);
            return response('bad json', 400);
        }

        $invoiceId = $data['invoiceId'] ?? null;
        $statusRaw = $data['status'] ?? null;
        $status    = strtolower((string)$statusRaw);

        // reference может быть в разных местах
        $reference =
            $data['reference']
            ?? ($data['merchantPaymInfo']['reference'] ?? null)
            ?? ($data['merchantPaymInfo']['referenceId'] ?? null)
            ?? null;

        Log::channel('payment')->info('[Monobank] webhook parsed', [
            'invoiceId' => $invoiceId,
            'status' => $status,
            'reference' => $reference,
        ]);

        if (!$invoiceId) {
            return response('ok', 200);
        }

        // ✅ Флаги для запуска TicketService
        $needFinalize = false;
        $legacyOrderIdForFinalize = null;

        try {
            DB::transaction(function () use (
                $invoiceId,
                $status,
                $data,
                $reference,
                &$needFinalize,
                &$legacyOrderIdForFinalize
            ) {
                /** @var Order|null $order */
                $order = Order::where('mono_invoice_id', (string)$invoiceId)->lockForUpdate()->first();

                // Fallback 1: по reference (если ты его отправляешь при создании invoice)
                if (!$order && $reference) {
                    // у тебя встречается uniqid (lowercase) и uniqId (camel) — пробуем оба варианта
                    $order = Order::where('uniqid', (string)$reference)->lockForUpdate()->first();
                    if (!$order) {
                        $order = Order::where('uniqId', (string)$reference)->lockForUpdate()->first();
                    }
                }

                if (!$order) {
                    Log::channel('payment')->warning('[Monobank] webhook: ORDER NOT FOUND', [
                        'invoiceId' => $invoiceId,
                        'status' => $status,
                        'reference' => $reference,
                    ]);

                    // Чтобы не терять событие — фиксируем в payments по payment_id
                    Payment::updateOrCreate(
                        ['payment_id' => (string)$invoiceId],
                        [
                            'user_id' => null,
                            'order_id' => 'UNKNOWN_' . (string)$invoiceId,
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

                // ✅ legacyOrderId: это то, что TicketService должен понимать
                $legacyOrderId = (string)($order->uniqid ?: ($order->uniqId ?? null) ?: ('ORDER_' . $order->id));

                // ✅ Всегда пишем mono_status
                $order->mono_status = $status ?: 'unknown';

                // ✅ Переход в paid / failed
                $alreadyPaid = ((int)($order->payment_status ?? 0) === 2);

                if (in_array($status, ['success', 'paid'], true)) {
                    $order->payment_status = 2;
                    $order->paid_at = $order->paid_at ?: now();
                } elseif (in_array($status, ['failure', 'failed', 'expired', 'reversed', 'cancelled', 'canceled', 'declined'], true)) {
                    if (!$alreadyPaid) {
                        $order->payment_status = 3;
                    }
                }

                $order->save();

                // ✅ Payment: ищем по order_id, иначе по payment_id
                $payloadJson = json_encode($data, JSON_UNESCAPED_UNICODE);

                $payment = Payment::where('order_id', $legacyOrderId)->lockForUpdate()->first();
                if (!$payment) {
                    $payment = Payment::where('payment_id', (string)$invoiceId)->lockForUpdate()->first();
                }

                if ($payment) {
                    $payment->order_id = $legacyOrderId;
                    $payment->payment_id = (string)$invoiceId;
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
                            'payment_id' => (string)$invoiceId,
                            'status' => $status ?: 'unknown',
                            'amount' => null,
                            'currency' => 'UAH',
                            'description' => "Monobank invoice #{$invoiceId}",
                            'response' => $payloadJson,
                            'paid_at' => in_array($status, ['success', 'paid'], true) ? now() : null,
                        ]
                    );

                    $payment = Payment::where('order_id', $legacyOrderId)->lockForUpdate()->first();
                }

                // ✅ КРИТИЧНО: финализируем не “один раз когда payment_status != 2”,
                // а если SUCCESS и еще нет отметки ticket_finalized_at в payments.response
                if (in_array($status, ['success', 'paid'], true)) {
                    $finalized = false;

                    if ($payment && $payment->response) {
                        $respArr = json_decode((string)$payment->response, true);
                        if (is_array($respArr) && !empty($respArr['ticket_finalized_at'])) {
                            $finalized = true;
                        }
                    }

                    if (!$finalized) {
                        $needFinalize = true;
                        $legacyOrderIdForFinalize = $legacyOrderId;
                    }
                }
            });
        } catch (\Throwable $e) {
            Log::channel('payment')->error('[Monobank] webhook TX FAILED', [
                'invoiceId' => $invoiceId,
                'status' => $status,
                'error' => $e->getMessage(),
                'trace' => mb_substr($e->getTraceAsString(), 0, 3000),
            ]);

            // возвращаем 200, чтобы не вызвать бесконечный ретрай при внутренних ошибках
            return response('ok', 200);
        }

        // ✅ ВНЕ транзакции: запуск TicketService
        if ($needFinalize && $legacyOrderIdForFinalize) {
            try {
                /** @var LegacyTicketService $ticketService */
                $ticketService = app(LegacyTicketService::class);

                Log::channel('payment')->info('[Monobank] FINALIZE START', [
                    'legacy_order_id' => $legacyOrderIdForFinalize,
                    'invoiceId' => $invoiceId,
                ]);

                // Важно: передаём legacy uniqid (у тебя это "order_....")
                $ticketService->processSuccessfulPayment((string)$legacyOrderIdForFinalize, $data);

                // ✅ помечаем, что финализация прошла (чтобы не дублировать)
                $p = Payment::where('order_id', (string)$legacyOrderIdForFinalize)->first();
                if ($p) {
                    $respArr = [];
                    if ($p->response) {
                        $tmp = json_decode((string)$p->response, true);
                        if (is_array($tmp)) $respArr = $tmp;
                    }
                    $respArr['ticket_finalized_at'] = now()->toIso8601String();
                    $p->response = json_encode($respArr, JSON_UNESCAPED_UNICODE);
                    $p->save();
                }

                Log::channel('payment')->info('[Monobank] FINALIZE OK', [
                    'legacy_order_id' => $legacyOrderIdForFinalize,
                    'invoiceId' => $invoiceId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('payment')->error('[Monobank] FINALIZE FAILED', [
                    'legacy_order_id' => $legacyOrderIdForFinalize,
                    'invoiceId' => $invoiceId,
                    'error' => $e->getMessage(),
                    'trace' => mb_substr($e->getTraceAsString(), 0, 3000),
                ]);
                // не ставим ticket_finalized_at => следующий success-webhook попробует снова
            }
        }

        return response('ok', 200);
    }
}
