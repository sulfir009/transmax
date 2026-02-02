<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\Client;
use App\Services\BonusService;
use App\Services\Payments\MonobankWebhookHandler;
use App\Services\Payments\PaymentFinalizer;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('monobank:test-webhook {invoiceId?} {--status=success} {--orderId=} {--uniqid=}', function () {
    if (app()->environment('production')) {
        $this->error('This command is disabled in production.');
        return;
    }

    $invoiceId = (string) ($this->argument('invoiceId') ?? '');
    $orderId = $this->option('orderId');
    $uniqid = $this->option('uniqid');
    $status = (string) ($this->option('status') ?? 'success');

    $order = null;
    if ($orderId) {
        $order = Order::find($orderId);
    }

    if (!$order && $uniqid) {
        $order = Order::where('uniqId', (string) $uniqid)
            ->orWhere('uniqid', (string) $uniqid)
            ->first();
    }

    if (!$order && $invoiceId !== '') {
        $order = Order::where('mono_invoice_id', $invoiceId)->first();
    }

    if (!$order) {
        $this->error('Order not found. Provide --orderId, --uniqid, or invoiceId argument.');
        return;
    }

    if ($invoiceId === '') {
        $invoiceId = (string) ($order->mono_invoice_id ?: ('TEST_INV_' . Str::uuid()));
    }

    if (!$order->mono_invoice_id) {
        $order->mono_invoice_id = $invoiceId;
        $order->mono_status = 'created';
        $order->save();
    }

    $legacyOrderId = (string) ($order->uniqid ?: ($order->uniqId ?? null) ?: ('ORDER_' . $order->id));
    $correlationId = PaymentFinalizer::buildCorrelationId($order->id, $legacyOrderId, $invoiceId);

    $payload = [
        'invoiceId' => $invoiceId,
        'status' => $status,
        'reference' => $legacyOrderId,
        'merchantPaymInfo' => [
            'reference' => $legacyOrderId,
        ],
    ];

    /** @var MonobankWebhookHandler $handler */
    $handler = app(MonobankWebhookHandler::class);
    $result = $handler->process($payload, 'monobank_cli', [
        'correlation_id' => $correlationId,
    ]);

    $this->info('Webhook simulation completed.');
    $this->line('Correlation ID: ' . $correlationId);
    $this->line('Result: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
})->purpose('Simulate Monobank webhook handling (non-production only).');

Artisan::command('bonuses:grant-initial {--dry-run} {--chunk=1000}', function () {
    $chunk = (int) $this->option('chunk') ?: 1000;
    $dryRun = (bool) $this->option('dry-run');

    $clientsTable = env('DB_PREFIX', 'mt') . '_clients';
    $ordersTable = env('DB_PREFIX', 'mt') . '_orders';

    $bonusService = app(BonusService::class);
    $amountCents = 10000;

    $processed = 0;
    $skipped = 0;
    $granted = 0;

    DB::table($clientsTable)
        ->join($ordersTable, $ordersTable . '.client_id', '=', $clientsTable . '.id')
        ->where($ordersTable . '.payment_status', 2)
        ->where($ordersTable . '.client_id', '>', 0)
        ->select($clientsTable . '.id')
        ->distinct()
        ->orderBy($clientsTable . '.id')
        ->chunk($chunk, function ($rows) use (
            $dryRun,
            $bonusService,
            $amountCents,
            &$processed,
            &$skipped,
            &$granted
        ) {
            foreach ($rows as $row) {
                $clientId = (int)($row->id ?? 0);
                if ($clientId <= 0) {
                    continue;
                }

                $processed++;

                $alreadyGranted = $bonusService->hasTransaction($clientId, 'initial_grant');

                if ($alreadyGranted) {
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $granted++;
                    continue;
                }

                $client = Client::find($clientId);
                if (!$client) {
                    $skipped++;
                    continue;
                }

                $bonusService->credit($client, $amountCents, 'initial_grant', [
                    'note' => 'initial campaign',
                ]);

                $granted++;
            }
        });

    $this->info('Initial bonus grant complete.');
    $this->line('Processed: ' . $processed);
    $this->line('Granted: ' . $granted);
    $this->line('Skipped (already granted or missing): ' . $skipped);
})->purpose('Grant initial бонусы клиентам с оплаченной историей.');
