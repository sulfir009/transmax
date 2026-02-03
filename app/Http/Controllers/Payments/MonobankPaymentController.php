<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\TourStopPrice;
use App\Services\Payments\MonobankAcquiringService;
use App\Services\Payments\PaymentFinalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonobankPaymentController extends Controller
{
    /**
     * Старт оплаты Monobank: создаём invoice и редиректим на страницу оплаты.
     *
     * ВАЖНО:
     * - Считаем, что TourStopPrice.price хранится В КОПЕЙКАХ (int).
     *   Поэтому в mono отправляем amount = priceKop * passengers (уже копейки).
     * - Для payments.order_id используем legacy uniqId (как у LiqPay: ORDER_xxx),
     *   чтобы TicketService потом работал и чтобы не было дублей из-за unique.
     */
    public function start(Order $order, MonobankAcquiringService $mono)
    {
        // 1) Получаем цену (из таблицы цен)
        $priceRow = TourStopPrice::query()
            ->where('tour_id', $order->tour_id)
            ->where('from_stop', $order->from_stop)
            ->where('to_stop', $order->to_stop)
            ->first();

        // 2) Цена в копейках (int)
        $priceKop = (int)($priceRow->price ?? 0);

        // Кол-во пассажиров (в старой схеме passagers)
        $passengers = max(1, (int)($order->passagers ?? 1));

        // Итог для mono в копейках
        $amountKop = $priceKop * $passengers;

        if ($amountKop < 1) {
            Log::error('[Monobank] invalid computed amount', [
                'order_db_id' => $order->id,
                'tour_id' => $order->tour_id,
                'from_stop' => $order->from_stop,
                'to_stop' => $order->to_stop,
                'price_raw' => $priceRow->price ?? null,
                'price_kop' => $priceKop,
                'passengers' => $passengers,
                'amount_kop' => $amountKop,
            ]);
            abort(400, 'Invalid amount');
        }

        // Для записи в payments (у тебя amount кастится в float, обычно хранят UAH)
        $amountUah = $amountKop / 100;

        // 3) Legacy uniqId (как в LiqPay)
        // ВАЖНО: TicketService и вся legacy-логика завязана на mt_orders.uniqId (строка ORDER_xxx)
        $legacyOrderId = (string)($order->uniqid ?: ('ORDER_' . $order->id));

        // 4) URLs (GET)
        // success/return/fail не должны быть точкой финализации! финализация только webhook.
        $successUrl   = route('payment.monobank.success', ['order' => $order->id]);
        $failUrl      = route('payment.monobank.fail', ['order' => $order->id]);
        $redirectUrl  = route('payment.monobank.return', ['order' => $order->id]);
        $webHookUrl   = (string) (config('services.monobank.webhook_url') ?: route('payment.monobank.webhook'));

        // 5) reference — кладём legacyOrderId (так проще дебажить + связка с mt_orders)
        $reference = $legacyOrderId;

        // 6) Создаём invoice в Mono
        $requestCorrelationId = PaymentFinalizer::buildCorrelationId($order->id, $legacyOrderId, null);
        Log::info('[Monobank] invoice create request', [
            'correlation_id' => $requestCorrelationId,
            'order_db_id' => $order->id,
            'legacy_order_id' => $legacyOrderId,
            'webhook_url' => $webHookUrl,
        ]);

        $invoice = $mono->createInvoice([
            'amount' => $amountKop, // ✅ копейки
            'ccy'    => 980,        // UAH
            'merchantPaymInfo' => [
                'reference'   => $reference,
                'destination' => "Оплата квитка, замовлення #{$order->id}",
                'webHookUrl'  => $webHookUrl,
            ],
            'redirectUrl' => $redirectUrl,
            'successUrl'  => $successUrl,
            'failUrl'     => $failUrl,
            'webHookUrl'  => $webHookUrl,
        ]);

        $invoiceId = $invoice['invoiceId'] ?? null;
        $pageUrl   = $invoice['pageUrl'] ?? null;

        if (!$invoiceId || !$pageUrl) {
            Log::error('[Monobank] invoice response missing fields', [
                'order_db_id' => $order->id,
                'legacy_order_id' => $legacyOrderId,
                'resp' => $invoice,
            ]);
            abort(502, 'Monobank invoice create failed');
        }

        // 7) Сохраняем связь invoice->order и создаём/обновляем payment без дублей
        DB::transaction(function () use (
            $order,
            $legacyOrderId,
            $invoiceId,
            $pageUrl,
            $amountKop,
            $amountUah
        ) {
            // Связь с mono invoice
            $order->mono_invoice_id = $invoiceId;
            $order->mono_page_url   = $pageUrl;
            $order->mono_status     = 'created';
            $order->save();

            // ✅ FIX: вместо create -> updateOrCreate по order_id (у тебя unique)
            Payment::updateOrCreate(
                ['order_id' => $legacyOrderId],
                [
                    'user_id'      => null,
                    'payment_id'   => $invoiceId,
                    'status'       => 'created',
                    'amount'       => $amountUah, // UAH
                    'currency'     => 'UAH',
                    'description'  => "Monobank invoice #{$invoiceId}",
                    'response'     => json_encode([
                        'invoiceId'   => $invoiceId,
                        'pageUrl'     => $pageUrl,
                        'amount_kop'  => $amountKop,
                        'amount_uah'  => $amountUah,
                    ], JSON_UNESCAPED_UNICODE),
                ]
            );
        });

        $correlationId = PaymentFinalizer::buildCorrelationId($order->id, $legacyOrderId, $invoiceId);
        Log::info('[Monobank] start invoice created', [
            'correlation_id' => $correlationId,
            'order_db_id' => $order->id,
            'legacy_order_id' => $legacyOrderId,
            'invoiceId' => $invoiceId,
            'webhook_url' => $webHookUrl,
        ]);

        // 8) Редирект на оплату
        return redirect()->away($pageUrl);
    }

    /**
     * return/success/fail — ТОЛЬКО редиректы.
     * Финализация билета/почты делается исключительно в webhook.
     *
     * ВАЖНО: у тебя route('booking.thank-you') падает из-за mt_booking,
     * поэтому ведём на реальный URL страницы.
     */
    public function return(Order $order)
    {
        $legacyOrderId = (string) ($order->uniqid ?: ($order->uniqId ?? null) ?: ('ORDER_' . $order->id));
        $query = http_build_query([
            'order_id' => $order->id,
            'uniqid' => $legacyOrderId,
            'payment_provider' => 'monobank',
        ]);

        return redirect('/dyakuyu-za-bronyuvannya-biletu?' . $query);
    }

    public function success(Order $order)
    {
        $legacyOrderId = (string) ($order->uniqid ?: ($order->uniqId ?? null) ?: ('ORDER_' . $order->id));
        $query = http_build_query([
            'order_id' => $order->id,
            'uniqid' => $legacyOrderId,
            'payment_provider' => 'monobank',
            'payment_hint' => 'success_url',
        ]);

        return redirect('/dyakuyu-za-bronyuvannya-biletu?' . $query);
    }

    public function fail(Order $order)
    {
        $legacyOrderId = (string) ($order->uniqid ?: ($order->uniqId ?? null) ?: ('ORDER_' . $order->id));
        $query = http_build_query([
            'order_id' => $order->id,
            'uniqid' => $legacyOrderId,
            'payment_provider' => 'monobank',
            'payment_hint' => 'fail_url',
        ]);

        return redirect('/dyakuyu-za-bronyuvannya-biletu?' . $query);
    }
}
