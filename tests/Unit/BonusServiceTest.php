<?php

namespace Tests\Unit;

use App\Models\BonusTransaction;
use App\Models\Client;
use App\Services\BonusService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class BonusServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('DB_PREFIX=mt');

        Schema::dropIfExists('mt_bonus_transactions');
        Schema::dropIfExists('mt_clients');
        Schema::dropIfExists('mt_orders');

        Schema::create('mt_clients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bonus_balance_cents')->default(0);
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

        Schema::create('mt_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bonus_redeemed_cents')->default(0);
            $table->unsignedBigInteger('bonus_cashback_cents')->default(0);
            $table->boolean('bonus_use_requested')->default(false);
        });
    }

    public function testCalculateMaxRedeemCents(): void
    {
        $service = new BonusService();

        $this->assertSame(10000, $service->calculateMaxRedeemCents(10000, 100000));
        $this->assertSame(15000, $service->calculateMaxRedeemCents(50000, 15000));
        $this->assertSame(1000, $service->calculateMaxRedeemCents(1000, 10000));
    }

    public function testCreditDebitBalance(): void
    {
        $client = Client::create(['bonus_balance_cents' => 0]);
        $service = new BonusService();

        $service->credit($client, 10000, 'grant_initial');
        $client->refresh();
        $this->assertSame(10000, $client->bonus_balance_cents);

        $service->debit($client, 3000, 'redeem', [], 10);
        $client->refresh();
        $this->assertSame(7000, $client->bonus_balance_cents);
    }

    public function testDebitCannotGoNegative(): void
    {
        $this->expectException(\RuntimeException::class);

        $client = Client::create(['bonus_balance_cents' => 5000]);
        $service = new BonusService();

        $service->debit($client, 6000, 'redeem', [], 11);
    }

    public function testInitialGrantIdempotencyCheck(): void
    {
        $client = Client::create(['bonus_balance_cents' => 0]);
        $service = new BonusService();

        $service->credit($client, 10000, 'grant_initial');
        $this->assertTrue($service->hasTransaction($client->id, 'grant_initial'));
    }

    public function testCashbackIdempotencyCheck(): void
    {
        $client = Client::create(['bonus_balance_cents' => 0]);
        $service = new BonusService();

        BonusTransaction::create([
            'client_id' => $client->id,
            'amount_cents' => 500,
            'type' => 'cashback',
            'order_id' => 55,
        ]);

        $this->assertTrue($service->hasTransaction($client->id, 'cashback', 55));
    }
}
