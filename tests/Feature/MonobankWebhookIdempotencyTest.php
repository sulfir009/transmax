<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Service\TicketService;
use App\Services\Payments\MonobankWebhookHandler;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class MonobankWebhookIdempotencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('DB_PREFIX=mt');
        config(['cache.default' => 'array']);
        Cache::flush();

        Schema::dropIfExists('payments');
        Schema::dropIfExists('mt_orders');

        Schema::create('mt_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uniqid')->nullable();
            $table->string('mono_invoice_id')->nullable();
            $table->string('mono_status')->nullable();
            $table->unsignedTinyInteger('payment_status')->default(1);
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('mono_modified_at')->nullable();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('order_id')->nullable();
            $table->string('payment_id')->nullable();
            $table->string('status')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('currency')->nullable();
            $table->text('description')->nullable();
            $table->text('response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function testWebhookSuccessIsIdempotent(): void
    {
        $order = Order::create([
            'uniqid' => 'ORDER_WEBHOOK',
            'mono_invoice_id' => 'INV_WEBHOOK',
            'payment_status' => 1,
        ]);

        $ticketService = Mockery::mock(TicketService::class);
        $ticketService->shouldReceive('processSuccessfulPayment')->once()->andReturn(true);
        $this->app->instance(TicketService::class, $ticketService);

        $handler = $this->app->make(MonobankWebhookHandler::class);

        $payload = [
            'invoiceId' => 'INV_WEBHOOK',
            'status' => 'success',
            'modifiedDate' => now()->toIso8601String(),
        ];

        $handler->process($payload, 'monobank_webhook', ['correlation_id' => 'test']);
        $handler->process($payload, 'monobank_webhook', ['correlation_id' => 'test']);

        $order->refresh();
        $this->assertSame(2, (int) $order->payment_status);
        $this->assertSame('success', $order->mono_status);
    }
}
