<?php

namespace Tests\Unit;

use App\Support\Money;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function testUahToKopeks(): void
    {
        $this->assertSame(95, Money::uahToKopeks('0.95'));
        $this->assertSame(100, Money::uahToKopeks('1'));
        $this->assertSame(120, Money::uahToKopeks('1.2'));
        $this->assertSame(12345, Money::uahToKopeks('123.45'));
    }

    public function testPriceToKopeksFromDb(): void
    {
        $this->assertSame(95, Money::priceToKopeksFromDb('0.95'));
        $this->assertSame(9500, Money::priceToKopeksFromDb('95'));
        $this->assertSame(9500, Money::priceToKopeksFromDb('9500'));
    }

    public function testKopeksToUahString(): void
    {
        $this->assertSame('0.95', Money::kopeksToUahString(95));
        $this->assertSame('1.00', Money::kopeksToUahString(100));
        $this->assertSame('1', Money::kopeksToUahString(100, true));
        $this->assertSame('123.45', Money::kopeksToUahString(12345));
    }

    public function testClamp(): void
    {
        $this->assertSame(50, Money::clamp(50, 0, 100));
        $this->assertSame(0, Money::clamp(-5, 0, 100));
        $this->assertSame(100, Money::clamp(120, 0, 100));
    }
}
