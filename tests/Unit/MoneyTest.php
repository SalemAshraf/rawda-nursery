<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_amounts_are_parsed_exactly_without_floats(): void
    {
        $this->assertSame(125050, Money::toMinor('1,250.50'));
        $this->assertSame(120, Money::toMinor('1.2'));
        $this->assertSame(0, Money::toMinor('0'));
        $this->assertSame(4294967295, Money::toMinor('42949672.95'));
    }

    public function test_ambiguous_or_unrepresentable_values_are_rejected(): void
    {
        foreach (['1.005', '1e5', '1e999', '-1', '1,2', '42949672.96', '99999999999999999999', 'NaN', ''] as $value) {
            $this->assertNull(Money::toMinor($value), $value);
        }
    }
}
