<?php

namespace Tests\Unit\Finance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Finance\Models\Tax;
use Tests\TestCase;

class TaxCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_exclusive_tax_correctly(): void
    {
        $tax = Tax::factory()->create([
            'rate' => 15.00,
            'is_inclusive' => false,
        ]);

        $amount = 1000;
        $calculatedTax = $tax->calculateTax($amount);

        $this->assertEquals(150, $calculatedTax);
    }

    public function test_calculates_inclusive_tax_correctly(): void
    {
        $tax = Tax::factory()->create([
            'rate' => 15.00,
            'is_inclusive' => true,
        ]);

        $grossAmount = 1150;
        $calculatedTax = $tax->calculateTax($grossAmount);

        $this->assertEquals(150, $calculatedTax);
    }

    public function test_rounds_inclusive_tax_half_up_to_four_decimals(): void
    {
        $tax = Tax::factory()->create([
            'rate' => 15.00,
            'is_inclusive' => true,
        ]);

        $this->assertSame(13.0435, $tax->calculateTax(100));
    }

    public function test_calculates_gross_from_net_exclusive(): void
    {
        $tax = Tax::factory()->create([
            'rate' => 15.00,
            'is_inclusive' => false,
        ]);

        $netAmount = 1000;
        $grossAmount = $tax->calculateGrossFromNet($netAmount);

        $this->assertEquals(1150, $grossAmount);
    }

    public function test_calculates_gross_from_net_inclusive(): void
    {
        $tax = Tax::factory()->create([
            'rate' => 15.00,
            'is_inclusive' => true,
        ]);

        $netAmount = 1000;
        $grossAmount = $tax->calculateGrossFromNet($netAmount);

        $this->assertEquals(1000, $grossAmount);
    }
}
