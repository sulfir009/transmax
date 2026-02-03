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
    public function start(Order $order, MonobankAcquiringService $mono)
    {
        // 1) Цена
        $priceRow = TourStopPrice::query()
            ->where('tour_id', $order->tour_id)
            ->where('from_stop', $order->from_stop)
            ->where('to_stop', $order->to_stop)
            ->first();

        // 2) Цена -> копейки (int)
        $priceRaw = $priceRow->price ?? 0;
        $priceKop = 0;

        if (is_numeric($priceRaw)) {
            $priceString = (string) $priceRaw;
            $hasDecimals = str_contains($priceString, '.') || str_contains($priceString, ',');
            $priceValue  = (float) str_replace(',', '.', $priceString);

            if ($hasDecimals) {
                // 1.23 => 123 коп
                $priceKop = (int) round($priceValue * 100);
            } elseif ($priceValue >= 1000) {
                // если в БД уже копейки типа 9500
                $priceKop = (int) round($priceValue);
            } else {
                // если в БД гривны без копеек типа 95
                $priceKop = (int) round($priceValue * 100);
            }
        }

        $passengers = max(1, (int)($order->passagers ?? 1));

        // 3) Итог в копейках
        $amountKop = $priceKop * $passengers;

        // 4) Бонусы (тоже копейки)
        $bonusRedeemedCents = (int)($order->bonus_redeemed_cents ?? 0);
        $useBonus = (int)($order->bonus_use_requested ?? 0) === 1;

        if ($useBonus && $bonusRedeemedCents > 0) {
            $amountKop = max(0, $amountKop - $bonusRedeemedCents);
        }

        if ($amountKop < 1) {
            Log::error('[Monobank] invalid computed amount', [
                'order_db_id' => $order->id,
                'price_raw' => $priceRow->price ?? null,
                'price_kop' => $priceKop,
                'passengers' => $passengers,
                'bonus_redeemed_cents' => $bonusRedeemedCents,
                'amount_kop' => $amountKop,
            ]);
            abort(400, 'Invalid amount');
        }

        // Для payments.amount (UAH) — красиво 2 знака
        $amountUah = $amountKop / 100;
        $amountUahFormatted = number_format($amountUah, 2, '.', '');

        // 5) legacy ID
        $legacyOrderId = (string)($order->uniqid ?: ('ORDER_' . $order->id));

        // 6) Проверяем, не существует ли уже инвойс и совпадает ли сумма
        [$existingInvoiceId, $existingPageUrl, $existingAmountKop, $existingPaymentStatus] =
            $this->getExistingInvoiceInfo($order, $legacyOrderId);

        // если инвойс есть и сумма такая же — используем его
        if ($existingInvoiceId && $existingPageUrl && $existingAmountKop === $amountKop) {
            Log::info('[Monobank] reuse existing invoice', [
                'order_db_id' => $order->id,
                'legacy_order_id' => $legacyOrderId,
                'invoiceId' => $existingInvoiceId,
                'amount_kop' => $amountKop,
                'payment_status' => $existingPaymentStatus,
            ]);

            return redirect()->away($existingPageUrl);
        }

        // если инвойс был, но сумма изменилась — инвалидируем старый (если по нему не платили)
        if ($existingInvoiceId) {
            try {
                // /api/merchant/invoice/remove — деактивация неоплаченного инвойса :contentReference[oaicite:3]{index=3}
                $mono->removeInvoice($existingInvoiceId);

                Log::info('[Monobank] old invoice removed (amount changed)', [
                    'order_db_id' => $order->id,
                    'legacy_order_id' => $legacyOrderId,
                    'old_invoiceId' => $existingInvoiceId,
                    'old_amount_kop' => $existingAmountKop,
                    'new_amount_kop' => $amountKop,
                ]);
            } catch (\Throwable $e) {
                // не блокируем оплату: если remove не дался (например, уже оплачено/expired) — просто создадим новый
                Log::warning('[Monobank] failed to remove old invoice, will create new', [
                    'order_db_id' => $order->id,
                    'legacy_order_id' => $legacyOrderId,
                    'old_invoiceId' => $existingInvoiceId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 7) URL’ы
        $successUrl   = route('payment.monobank.success', ['order' => $order->id]);
        $failUrl      = route('payment.monobank.fail', ['order' => $order->id]);
        $redirectUrl  = route('payment.monobank.return', ['order' => $order->id]);
        $webHookUrl   = (string) (config('services.monobank.webhook_url') ?: route('payment.monobank.webhook'));

        $reference = $legacyOrderId;

        $requestCorrelationId = PaymentFinalizer::buildCorrelationId($order->id, $legacyOrderId, null);

        Log::info('[Monobank] invoice create request', [
            'correlation_id' => $requestCorrelationId,
            'order_db_id' => $order->id,
            'legacy_order_id' => $legacyOrderId,
            'amount_kop' => $amountKop,
            'amount_uah' => $amountUahFormatted,
            'webhook_url' => $webHookUrl,
        ]);

        // 8) Создаём invoice — ВАЖНО: amount = INT в копейках :contentReference[oaicite:4]{index=4}
        $invoice = $mono->createInvoice([
            'amount' => $amountKop, // <-- ключевой фикс
            'ccy'    => 980,
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

        // 9) Сохраняем order + payment
        DB::transaction(function () use (
            $order,
            $legacyOrderId,
            $invoiceId,
            $pageUrl,
            $amountKop,
            $amountUahFormatted
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
                    'amount'       => $amountUahFormatted, // лучше хранить как decimal(10,2)
                    'currency'     => 'UAH',
                    'description'  => "Monobank invoice #{$invoiceId}",
                    'response'     => json_encode([
                        'invoiceId'   => $invoiceId,
                        'pageUrl'     => $pageUrl,
                        'amount_kop'  => $amountKop,
                        'amount_uah'  => $amountUahFormatted,
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
            'amount_kop' => $amountKop,
            'amount_uah' => $amountUahFormatted,
        ]);

        return redirect()->away($pageUrl);
    }

    private function getExistingInvoiceInfo(Order $order, string $legacyOrderId): array
    {
        $invoiceId = $order->mono_invoice_id ?? null;
        $pageUrl   = $order->mono_page_url ?? null;

        $amountKop = null;
        $paymentStatus = null;

        $payment = Payment::query()->where('order_id', $legacyOrderId)->first();
        if ($payment) {
            $paymentStatus = $payment->status;

            $resp = $payment->response;
            if (is_string($resp) && $resp !== '') {
                $decoded = json_decode($resp, true);
                if (is_array($decoded)) {
                    $amountKop = isset($decoded['amount_kop']) ? (int)$decoded['amount_kop'] : null;

                    // если в order нет pageUrl/invoiceId, подстрахуемся из response
                    $invoiceId = $invoiceId ?: ($decoded['invoiceId'] ?? null);
                    $pageUrl   = $pageUrl   ?: ($decoded['pageUrl']   ?? null);
                }
            }
        }

        return [$invoiceId, $pageUrl, $amountKop, $paymentStatus];
    }

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
