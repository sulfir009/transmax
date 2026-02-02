<?php

namespace Tests\Unit;

use App\Models\BonusTransaction;
use App\Models\Client;
use App\Models\Order;
use App\Service\TicketService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class BonusWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('DB_PREFIX=mt');

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        Schema::dropIfExists('mt_bonus_transactions');
        Schema::dropIfExists('mt_orders');
        Schema::dropIfExists('mt_clients');

        Schema::create('mt_clients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('second_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedBigInteger('bonus_balance_cents')->default(0);
        });

        Schema::create('mt_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('client_email')->nullable();
            $table->unsignedBigInteger('bonus_redeemed_cents')->default(0);
            $table->unsignedBigInteger('bonus_cashback_cents')->default(0);
            $table->boolean('bonus_use_requested')->default(false);
            $table->unsignedTinyInteger('payment_status')->default(1);
        });

        Schema::create('mt_bonus_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('client_id');
            $table->bigInteger('amount_cents');
            $table->string('type', 50);
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function testItGrantsInitialBonusOncePerClient(): void
    {
        $client = Client::create([
            'email' => 'user@example.com',
            'bonus_balance_cents' => 0,
        ]);

        Order::create([
            'client_id' => $client->id,
            'payment_status' => 2,
        ]);

        Artisan::call('bonuses:grant-initial');

        $client->refresh();
        $this->assertSame(10000, $client->bonus_balance_cents);
        $this->assertSame(1, BonusTransaction::where('type', 'grant_initial')->count());

        Artisan::call('bonuses:grant-initial');

        $client->refresh();
        $this->assertSame(10000, $client->bonus_balance_cents);
        $this->assertSame(1, BonusTransaction::where('type', 'grant_initial')->count());
    }

    public function testItAppliesRedeemAndCashbackIdempotentlyOnPaymentFinalization(): void
    {
        $client = Client::create([
            'email' => 'redeem@example.com',
            'bonus_balance_cents' => 3000,
        ]);

        $order = Order::create([
            'client_id' => $client->id,
            'client_email' => $client->email,
            'bonus_use_requested' => 1,
        ]);

        $ticketInfo = (object) ['price' => 100];

        $service = new TicketService();
        $method = new ReflectionMethod(TicketService::class, 'applyBonusOperations');
        $method->setAccessible(true);

        $method->invoke($service, $order->fresh(), $ticketInfo, 1, ['payment_provider' => 'monobank'], 'test');

        $order->refresh();
        $client->refresh();

        $this->assertSame(3000, $order->bonus_redeemed_cents);
        $this->assertSame(350, $order->bonus_cashback_cents);
        $this->assertSame(350, $client->bonus_balance_cents);
        $this->assertSame(1, BonusTransaction::where('type', 'redeem')->count());
        $this->assertSame(1, BonusTransaction::where('type', 'cashback')->count());

        $method->invoke($service, $order->fresh(), $ticketInfo, 1, ['payment_provider' => 'monobank'], 'test');

        $order->refresh();
        $client->refresh();

        $this->assertSame(3000, $order->bonus_redeemed_cents);
        $this->assertSame(350, $order->bonus_cashback_cents);
        $this->assertSame(350, $client->bonus_balance_cents);
        $this->assertSame(1, BonusTransaction::where('type', 'redeem')->count());
        $this->assertSame(1, BonusTransaction::where('type', 'cashback')->count());
    }

    public function testItBindsOrderToClientByEmailWhenClientIdMissing(): void
    {
        $client = Client::create([
            'email' => 'bind@example.com',
            'bonus_balance_cents' => 0,
        ]);

        $order = Order::create([
            'client_id' => 0,
            'client_email' => $client->email,
            'bonus_use_requested' => 0,
        ]);

        $ticketInfo = (object) ['price' => 100];

        $service = new TicketService();
        $method = new ReflectionMethod(TicketService::class, 'applyBonusOperations');
        $method->setAccessible(true);

        $method->invoke($service, $order->fresh(), $ticketInfo, 1, ['payment_provider' => 'monobank'], 'test');

        $order->refresh();
        $client->refresh();

        $this->assertSame($client->id, (int) $order->client_id);
        $this->assertSame(500, $order->bonus_cashback_cents);
        $this->assertSame(500, $client->bonus_balance_cents);
    }

    public function testAdminCanCreditManualBonus(): void
    {
        $_SESSION['admin'] = ['id' => 7];

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $client = Client::create([
            'email' => 'admin@example.com',
            'bonus_balance_cents' => 0,
        ]);

        $response = $this->post(route('admin.bonuses.credit'), [
            'client_id' => $client->id,
            'amount_uah' => 123.45,
            'comment' => 'manual test',
        ]);

        $response->assertStatus(302);

        $client->refresh();
        $this->assertSame(12345, $client->bonus_balance_cents);

        $transaction = BonusTransaction::where('type', 'manual')->first();
        $this->assertNotNull($transaction);
        $this->assertSame(12345, (int) $transaction->amount_cents);
        $this->assertSame('manual test', $transaction->meta['comment'] ?? null);
        $this->assertSame(7, (int) $transaction->admin_id);
    }
}
