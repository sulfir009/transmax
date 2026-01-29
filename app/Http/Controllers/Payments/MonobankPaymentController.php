<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\TourStopPrice;
use App\Services\Payments\MonobankAcquiringService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonobankPaymentController extends Controller
{
    /**
     * Старт оплаты Monobank: создаём invoice и редиректим на страницу оплаты.
     *
     * ВАЖНО:
     * - TourStopPrice.price хранится В КОПЕЙКАХ (int).
     * - В mono отправляем amount в копейках.
     * - payments.order_id — legacy идентификатор (uniqid или ORDER_{id}) чтобы не было дублей.
     */
    public function start(Order $order, MonobankAcquiringService $mono)
    {
        // 1) Цена
        $priceRow = TourStopPrice::query()
            ->where('tour_id', $order->tour_id)
            ->where('from_stop', $order->from_stop)
            ->where('to_stop', $order->to_stop)
            ->first();

        // 2) Цена в копейках
        $priceKop = (int)($priceRow?->price ?? 0);

        // 3) Пассажиры (legacy поле passagers)
        $passengers = max(1, (int)($order->passagers ?? 1));

        // 4) Сумма в копейках
        $amountKop = $priceKop * $passengers;

        if ($amountKop < 1) {
            Log::error('[Monobank] invalid computed amount', [
                'order_db_id' => $order->id,
                'tour_id' => $order->tour_id,
                'from_stop' => $order->from_stop,
                'to_stop' => $order->to_stop,
                'price_raw' => $priceRow?->price,
                'price_kop' => $priceKop,
                'passengers' => $passengers,
                'amount_kop' => $amountKop,
            ]);
            abort(400, 'Invalid amount');
        }

        // Для payments.amount (обычно UAH)
        $amountUah = $amountKop / 100;

        // 5) legacy order id для таблицы payments (у тебя unique по order_id)
        $legacyOrderId = (string)($order->uniqid ?: ('ORDER_' . $order->id));

        // 6) URLs
        $thankYouBase = 'https://maxtransltd.com/dyakuyu-za-bronyuvannya-biletu';

        $uniqid = (string)($order->uniqid ?: ('order_' . $order->id));

        $redirectUrl = $thankYouBase . '?' . http_build_query([
            'order_id' => (int)$order->id,
            'uniqid'   => $uniqid,
        ], '', '&', PHP_QUERY_RFC3986);

        // success/fail тоже ведём на thank-you, чтобы в URL всегда были order_id/uniqid
        $successUrl = $redirectUrl . '&payment_hint=success_url';
        $failUrl    = $redirectUrl . '&payment_hint=fail_url';

        $webHookUrl = route('payment.monobank.webhook');

        // reference кладём legacyOrderId (удобно связывать webhook с заказом)
        $reference = $legacyOrderId;

        // 7) Создаём invoice
        $invoice = $mono->createInvoice([
            'amount' => $amountKop,
            'ccy'    => 980,
            'reference' => $reference,
            'merchantPaymInfo' => [
                'reference'   => $reference,
                'destination' => "Оплата квитка, замовлення #{$order->id}",
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

        // 8) Сохраняем invoice + payment (без дублей)
        DB::transaction(function () use (
            $order,
            $legacyOrderId,
            $invoiceId,
            $pageUrl,
            $amountKop,
            $amountUah
        ) {
            $order->mono_invoice_id = $invoiceId;
            $order->mono_page_url   = $pageUrl;
            $order->mono_status     = 'created';
            $order->save();

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

        // 9) Редирект на страницу оплаты mono
        return redirect()->away($pageUrl);
    }

    /**
     * return/success/fail — ТОЛЬКО редиректы.
     * Финализация (билет/почта) — только webhook.
     */
    protected function thankYouRedirect(Order $order, string $hint)
    {
        $uniqid = (string)($order->uniqid ?: ('order_' . $order->id));

        $params = [
            'order_id' => (int)$order->id,
            'uniqid'   => $uniqid,
            'payment_provider' => 'monobank',
            'payment_hint' => $hint,
        ];

        $url = 'https://maxtransltd.com/dyakuyu-za-bronyuvannya-biletu?' . http_build_query(
            $params,
            '',
            '&',
            PHP_QUERY_RFC3986
        );

        return redirect()->away($url);
    }

    public function return(Order $order)
    {
        return $this->thankYouRedirect($order, 'return_url');
    }

    public function success(Order $order)
    {
        return $this->thankYouRedirect($order, 'success_url');
    }

    public function fail(Order $order)
    {
        return $this->thankYouRedirect($order, 'fail_url');
    }
}
