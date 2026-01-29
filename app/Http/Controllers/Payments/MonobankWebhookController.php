<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Services\Payments\MonobankAcquiringService;
use App\Services\Payments\MonobankWebhookHandler;
use App\Services\Payments\PaymentFinalizer;
use Illuminate\Http\Request;
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
    public function handle(Request $request, MonobankAcquiringService $mono, MonobankWebhookHandler $handler)
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
        $correlationId = PaymentFinalizer::buildCorrelationId(null, null, (string) $invoiceId);

        Log::info('[Monobank] webhook parsed payload', [
            'correlation_id' => $correlationId,
            'payload' => $data,
        ]);

        if (!$invoiceId) {
            // если нет invoiceId — нечего привязать
            return response('ok', 200);
        }

        try {
            $handler->process($data, 'monobank_webhook', [
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::error('[Monobank] webhook handle failed', [
                'correlation_id' => $correlationId,
                'invoiceId' => $invoiceId,
                'error' => $e->getMessage(),
            ]);

            // ВАЖНО: моно будет ретраить, если не 200.
            // Мы возвращаем 200, чтобы не устроить бесконечные ретраи на проде.
            return response('ok', 200);
        }

        return response('ok', 200);
    }
}
