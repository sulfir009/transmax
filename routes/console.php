<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use App\Models\Order;
use App\Services\Payments\MonobankWebhookHandler;
use App\Services\Payments\PaymentFinalizer;

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
