<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\TourStopPrice;
use App\Services\Payments\MonobankAcquiringService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MonobankBonusAmountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('DB_PREFIX=mt');

        Schema::dropIfExists('payments');
        Schema::dropIfExists('mt_tours_stops_prices');
        Schema::dropIfExists('mt_orders');
        Schema::dropIfExists('mt_clients');

        Schema::create('mt_clients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bonus_balance_cents')->default(0);
        });

        Schema::create('mt_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('tour_id')->nullable();
            $table->unsignedBigInteger('from_stop')->nullable();
            $table->unsignedBigInteger('to_stop')->nullable();
            $table->unsignedInteger('passagers')->default(1);
            $table->string('uniqid')->nullable();
            $table->unsignedBigInteger('bonus_redeemed_cents')->default(0);
            $table->boolean('bonus_use_requested')->default(false);
            $table->string('mono_invoice_id')->nullable();
            $table->string('mono_status')->nullable();
            $table->text('mono_page_url')->nullable();
            $table->unsignedTinyInteger('payment_status')->default(1);
            $table->timestamp('paid_at')->nullable();
        });

        Schema::create('mt_tours_stops_prices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tour_id');
            $table->unsignedBigInteger('from_stop');
            $table->unsignedBigInteger('to_stop');
            $table->string('price');
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

    public function testInvoiceAmountMatchesBonusAdjustedTotal(): void
    {
        $client = Client::create(['bonus_balance_cents' => 5]);

        $order = Order::create([
            'client_id' => $client->id,
            'tour_id' => 1,
            'from_stop' => 10,
            'to_stop' => 20,
            'passagers' => 1,
            'uniqid' => 'ORDER_TEST',
            'bonus_use_requested' => 1,
            'bonus_redeemed_cents' => 5,
        ]);

        TourStopPrice::create([
            'tour_id' => 1,
            'from_stop' => 10,
            'to_stop' => 20,
            'price' => '1.00',
        ]);

        $fake = new class extends MonobankAcquiringService {
            public array $payload = [];

            public function createInvoice(array $payload): array
            {
                $this->payload = $payload;
                return [
                    'invoiceId' => 'INV_TEST',
                    'pageUrl' => 'https://example.com/pay',
                ];
            }
        };

        $this->app->instance(MonobankAcquiringService::class, $fake);

        $response = $this->get(route('payment.monobank.start', ['order' => $order->id]));

        $response->assertStatus(302);
        $response->assertRedirect('https://example.com/pay');

        $this->assertSame(95, $fake->payload['amount']);

        $payment = \DB::table('payments')->where('order_id', 'ORDER_TEST')->first();
        $this->assertNotNull($payment);

        $decoded = json_decode($payment->response, true);
        $this->assertSame('0.95', $decoded['amount_uah'] ?? null);
    }
}
