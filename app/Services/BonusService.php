<?php

namespace App\Services;

use App\Models\BonusTransaction;
use App\Models\Client;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class BonusService
{
    public function hasTransaction(int $clientId, string $type, ?int $orderId = null): bool
    {
        $query = BonusTransaction::where('client_id', $clientId)
            ->where('type', $type);

        if ($orderId !== null) {
            $query->where('order_id', $orderId);
        }

        return $query->exists();
    }

    public function getBalanceCents(Client $client): int
    {
        return (int) $client->bonus_balance_cents;
    }

    public function credit(
        Client $client,
        int $amountCents,
        string $type,
        array $meta = [],
        ?int $orderId = null,
        ?int $adminId = null
    ): BonusTransaction {
        if ($amountCents <= 0) {
            throw new InvalidArgumentException('Credit amount must be positive.');
        }

        return DB::transaction(function () use ($client, $amountCents, $type, $meta, $orderId, $adminId) {
            $locked = Client::where('id', $client->id)->lockForUpdate()->firstOrFail();
            $newBalance = (int) $locked->bonus_balance_cents + $amountCents;

            $transaction = BonusTransaction::create([
                'client_id' => $locked->id,
                'amount_cents' => $amountCents,
                'type' => $type,
                'order_id' => $orderId,
                'admin_id' => $adminId,
                'meta' => $meta,
            ]);

            $locked->bonus_balance_cents = $newBalance;
            $locked->save();

            Log::channel('bonus')->info('[BONUS_CREDIT]', [
                'transaction_id' => $transaction->id,
                'client_id' => $locked->id,
                'order_id' => $orderId,
                'type' => $type,
                'amount_cents' => $amountCents,
                'balance_cents' => $newBalance,
                'admin_id' => $adminId,
            ]);

            return $transaction;
        });
    }

    public function debit(
        Client $client,
        int $amountCents,
        string $type = 'redeem',
        array $meta = [],
        ?int $orderId = null
    ): BonusTransaction {
        if ($amountCents <= 0) {
            throw new InvalidArgumentException('Debit amount must be positive.');
        }

        return DB::transaction(function () use ($client, $amountCents, $type, $meta, $orderId) {
            $locked = Client::where('id', $client->id)->lockForUpdate()->firstOrFail();
            $current = (int) $locked->bonus_balance_cents;

            if ($current < $amountCents) {
                throw new RuntimeException('Insufficient bonus balance.');
            }

            $transaction = BonusTransaction::create([
                'client_id' => $locked->id,
                'amount_cents' => -$amountCents,
                'type' => $type,
                'order_id' => $orderId,
                'meta' => $meta,
            ]);

            $locked->bonus_balance_cents = $current - $amountCents;
            $locked->save();

            Log::channel('bonus')->info('[BONUS_DEBIT]', [
                'transaction_id' => $transaction->id,
                'client_id' => $locked->id,
                'order_id' => $orderId,
                'type' => $type,
                'amount_cents' => -$amountCents,
                'balance_cents' => $locked->bonus_balance_cents,
            ]);

            return $transaction;
        });
    }

    public function calculateMaxRedeemCents(int $balanceCents, int $payableCents): int
    {
        if ($balanceCents <= 0 || $payableCents <= 0) {
            return 0;
        }
        return max(0, min($balanceCents, $payableCents));
    }

    public function formatToUah(int $amountCents): string
    {
        return Money::kopeksToUahString($amountCents, true);
    }
}
