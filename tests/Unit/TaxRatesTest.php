<?php

namespace RattingManager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RattingManager\Services\Ratting\TaxRates;

/**
 * The two formulas every money figure rests on: what a character received,
 * worked out from the corporation's cut, and ISK ratted as the corporation
 * counts it.
 */
final class TaxRatesTest extends TestCase
{
    public function test_net_from_cut_at_fifteen_percent(): void
    {
        // A 1.5M cut at 15% means the character received 8.5M.
        $this->assertSame(8500000.0, TaxRates::netFromCut(1500000, 0.15));
    }

    public function test_net_from_cut_rounds_to_the_cent(): void
    {
        // 100 at 30%: 233.333... received, kept to the cent.
        $this->assertSame(233.33, TaxRates::netFromCut(100, 0.3));
    }

    public function test_gross_from_cut_is_the_cut_over_the_rate(): void
    {
        // A 1.5M cut at 15% is 10M ratted: 8.5M to the pilot, 1.5M to the corporation.
        $this->assertEqualsWithDelta(10000000.0, TaxRates::grossFromCut(1500000, 0.15), 0.0001);
    }

    public function test_cut_plus_net_is_gross(): void
    {
        foreach ([0.05, 0.1, 0.15, 0.25, 0.5] as $rate) {
            $cut = 1234567.0;
            $this->assertEqualsWithDelta(
                TaxRates::grossFromCut($cut, $rate),
                $cut + TaxRates::netFromCut($cut, $rate),
                0.01,
                "at a {$rate} rate"
            );
        }
    }

    public function test_a_month_of_tax_matches_the_ratting_page(): void
    {
        // EVE-RO, September 2026: 38.48 B of ratting tax at 15% is 256.5 B ratted.
        $this->assertEqualsWithDelta(256.53e9, TaxRates::grossFromCut(38.48e9, 0.15), 0.01e9);
    }
}
