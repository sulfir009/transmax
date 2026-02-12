<?php

namespace App\Services\Payments;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentFinalizer
{
    public const PAYMENT_STATUS_PENDING = 1;
    public const PAYMENT_STATUS_PAID    = 2;

    /**
     * Build correlation id for logs/debug.
     * ВАЖНО: MonobankPaymentController вызывает это статически, поэтому static.
     */
    public static function buildCorrelationId(int $orderDbId, string $legacyOrderId, ?string $invoiceId): string
    {
        $invoiceId = $invoiceId ?: 'no-invoice';
        return $orderDbId . '|' . $legacyOrderId . '|' . $invoiceId;
    }

    /**
     * Адаптер под контроллер, который вызывает:
     * $finalizer->finalizeMonobankPaidIfNeeded($order, $remoteStatus, 'polling');
     */
    public function finalizeMonobankPaidIfNeeded(Order $order, array $remoteStatus, string $source = 'polling'): array
    {
        $legacyOrderId = (string) ($order->uniqid ?: ($order->uniqId ?? null) ?: ('ORDER_' . $order->id));
        $invoiceId = (string) ($remoteStatus['invoiceId'] ?? $order->mono_invoice_id ?? '');
        $monoStatus = (string) ($remoteStatus['status'] ?? $order->mono_status ?? 'unknown');

        return $this->finalizeMonobank(
            (int) $order->id,
            $legacyOrderId,
            $invoiceId,
            $monoStatus,
            $source,
            $remoteStatus
        );
    }

    public function finalize(Order $order, array $payload, string $source = 'webhook'): array
    {
        $legacyOrderId = (string) ($order->uniqid ?: ($order->uniqId ?? null) ?: ('ORDER_' . $order->id));
        $invoiceId = (string) ($payload['invoiceId'] ?? $order->mono_invoice_id ?? '');
        $monoStatus = (string) ($payload['status'] ?? $order->mono_status ?? 'unknown');

        return $this->finalizeMonobank(
            (int) $order->id,
            $legacyOrderId,
            $invoiceId,
            $monoStatus,
            $source,
            $payload
        );
    }

    /**
     * Финализация оплаты Monobank.
     *
     * @param int         $orderDbId       ID заказа в mt_orders.id (например 1000976)
     * @param string      $legacyOrderId   uniqid из legacy (например order_697ca1cf...)
     * @param string      $invoiceId       invoiceId Monobank (например 260130iSrULUJgB1Qz9)
     * @param string      $monoStatus      статус Monobank (success / pending / failure / expired / reversed ...)
     * @param string      $source          источник финализации (polling/callback/manual)
     * @param array|null  $remoteStatus    полный ответ Monobank (если есть) - чтобы взять modifiedDate
     *
     * @return array{
     *   correlation_id: string,
     *   order_db_id: int,
     *   legacy_order_id: string,
     *   invoice_id: string,
     *   mono_status: string,
     *   source: string,
     *   already_finalized: bool,
     *   finalized: bool,
     *   payment_status: int|null,
     *   paid_at: string|null,
     *   error: string|null
     * }
     */
    public function finalizeMonobank(
        int $orderDbId,
        string $legacyOrderId,
        string $invoiceId,
        string $monoStatus,
        string $source = 'polling',
        ?array $remoteStatus = null
    ): array {
        $monoStatus = strtolower(trim((string)$monoStatus));

        $correlationId = self::buildCorrelationId($orderDbId, $legacyOrderId, $invoiceId);

        $result = [
            'correlation_id'    => $correlationId,
            'order_db_id'       => $orderDbId,
            'legacy_order_id'   => $legacyOrderId,
            'invoice_id'        => $invoiceId,
            'mono_status'       => $monoStatus,
            'source'            => $source,
            'already_finalized' => false,
            'finalized'         => false,
            'payment_status'    => null,
            'paid_at'           => null,
            'error'             => null,
        ];

        try {
            return DB::transaction(function () use (
                $orderDbId,
                $legacyOrderId,
                $invoiceId,
                $monoStatus,
                $source,
                $remoteStatus,
                $correlationId,
                $result
            ) {
                // 1) Лочим строку заказа, чтобы параллельные poll/callback не конфликтовали
                $order = DB::table('mt_orders')
                    ->where('id', $orderDbId)
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    Log::error('[PaymentFinalizer] monobank finalize failed - order not found', [
                        'correlation_id'  => $correlationId,
                        'order_db_id'     => $orderDbId,
                        'legacy_order_id' => $legacyOrderId,
                        'invoice_id'      => $invoiceId,
                        'mono_status'     => $monoStatus,
                        'source'          => $source,
                    ]);

                    $result['error'] = 'Order not found: mt_orders.id=' . $orderDbId;
                    return $result;
                }

                $currentPaymentStatus = isset($order->payment_status) ? (int)$order->payment_status : null;

                // 2) Идемпотентность: если уже оплачено — выходим
                $alreadyFinalized = ($currentPaymentStatus === self::PAYMENT_STATUS_PAID);

                Log::info('[PaymentFinalizer] monobank finalize check', [
                    'correlation_id'    => $correlationId,
                    'source'            => $source,
                    'invoice_id'        => $invoiceId,
                    'order_db_id'       => $orderDbId,
                    'legacy_order_id'   => $legacyOrderId,
                    'mono_status'       => $monoStatus,
                    'already_finalized' => $alreadyFinalized,
                ]);

                $result['already_finalized'] = $alreadyFinalized;
                $result['payment_status']    = $currentPaymentStatus;

                if ($alreadyFinalized) {
                    // на всякий: если invoice_id ещё не записан — можно записать (не мешает)
                    try {
                        if ((isset($order->mono_invoice_id) && !$order->mono_invoice_id) || !isset($order->mono_invoice_id)) {
                            DB::table('mt_orders')->where('id', $orderDbId)->update([
                                'mono_invoice_id' => $invoiceId,
                            ]);
                        }
                    } catch (Throwable $e) {
                        // не критично
                    }

                    if (isset($order->paid_at) && $order->paid_at) {
                        $result['paid_at'] = (string)$order->paid_at;
                    }
                    return $result;
                }

                // 3) Если статус НЕ success:
                // - payment_status НЕ трогаем
                // - но mono_status и mono_invoice_id пишем, чтобы не терять трекинг
                if ($monoStatus !== 'success') {
                    $upd = [
                        'mono_status'     => $monoStatus,
                        'mono_invoice_id' => $invoiceId,
                    ];

                    $updated = DB::table('mt_orders')
                        ->where('id', $orderDbId)
                        ->update($upd);

                    Log::info('[PaymentFinalizer] monobank finalize skipped (not success)', [
                        'correlation_id' => $correlationId,
                        'order_db_id'    => $orderDbId,
                        'mono_status'    => $monoStatus,
                        'updated_rows'   => $updated,
                    ]);

                    $result['finalized']      = false;
                    $result['payment_status'] = $currentPaymentStatus;
                    return $result;
                }

                // 4) success -> выставляем paid
                $paidAt = $this->resolvePaidAt($remoteStatus);

                $updateData = [
                    'payment_status'  => self::PAYMENT_STATUS_PAID,
                    'mono_status'     => 'success',
                    'mono_invoice_id' => $invoiceId,
                    'paid_at'         => $paidAt,
                    'ticket_return'   => 0,
                ];

                $updated = DB::table('mt_orders')
                    ->where('id', $orderDbId)
                    ->update($updateData);

                if ($updated < 1) {
                    Log::warning('[PaymentFinalizer] monobank finalize - no rows updated', [
                        'correlation_id' => $correlationId,
                        'order_db_id'    => $orderDbId,
                        'invoice_id'     => $invoiceId,
                        'mono_status'    => $monoStatus,
                        'updateData'     => $updateData,
                    ]);

                    $result['error'] = 'Finalize executed but no rows updated (mt_orders)';
                    return $result;
                }

                Log::channel('payment')->info('[PaymentFinalizer] monobank finalize success', [
                    'correlation_id' => $correlationId,
                    'order_db_id'    => $orderDbId,
                    'legacy_order_id'=> $legacyOrderId,
                    'invoice_id'     => $invoiceId,
                    'mono_status'    => $monoStatus,
                    'paid_at'        => $paidAt,
                    'updated_rows'   => $updated,
                    'updated_fields' => $updateData,
                ]);

                $result['finalized']      = true;
                $result['payment_status'] = self::PAYMENT_STATUS_PAID;
                $result['paid_at']        = $paidAt;

                return $result;
            }, 3);
        } catch (Throwable $e) {
            Log::error('[PaymentFinalizer] monobank finalize failed (exception)', [
                'correlation_id'  => $correlationId,
                'order_db_id'     => $orderDbId,
                'legacy_order_id' => $legacyOrderId,
                'invoice_id'      => $invoiceId,
                'mono_status'     => $monoStatus,
                'source'          => $source,
                'error'           => $e->getMessage(),
            ]);

            $result['error'] = $e->getMessage();
            return $result;
        }
    }

    /**
     * paid_at:
     * - если Monobank прислал modifiedDate -> берём его (UTC)
     * - иначе now()
     */
    private function resolvePaidAt(?array $remoteStatus): string
    {
        try {
            if (is_array($remoteStatus)) {
                $modified = null;

                if (isset($remoteStatus['modifiedDate']) && $remoteStatus['modifiedDate']) {
                    $modified = (string)$remoteStatus['modifiedDate'];
                } elseif (isset($remoteStatus['createdDate']) && $remoteStatus['createdDate']) {
                    $modified = (string)$remoteStatus['createdDate'];
                }

                if ($modified) {
                    $dt = Carbon::parse($modified)->utc();
                    return $dt->format('Y-m-d H:i:s');
                }
            }
        } catch (Throwable $e) {
            // ignore
        }

        return Carbon::now()->format('Y-m-d H:i:s');
    }
}
