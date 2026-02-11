<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_price_with_decimals_is_converted_to_kopeks(): void
    {
        $this->assertSame(1234, Money::priceToKopeksFromDb('12.34'));
    }

    public function test_integer_price_is_treated_as_hryvnia_and_scaled_to_kopeks(): void
    {
        $this->assertSame(300000, Money::priceToKopeksFromDb('3000'));
    }

    public function test_small_integer_price_is_treated_as_hryvnia_and_scaled_to_kopeks(): void
    {
        $this->assertSame(9500, Money::priceToKopeksFromDb('95'));
    }
}
