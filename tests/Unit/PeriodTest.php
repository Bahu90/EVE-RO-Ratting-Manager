<?php

namespace RattingManager\Tests\Unit;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use RattingManager\Services\Ratting\Period;

/** The periods every page picks from, in EVE time. */
final class PeriodTest extends TestCase
{
    private function now(): Carbon
    {
        return Carbon::create(2026, 10, 15, 12, 0, 0, 'UTC');
    }

    public function test_unknown_keys_fall_back_to_this_month(): void
    {
        $p = Period::from('nonsense', $this->now());

        $this->assertSame('this_month', $p->key);
        $this->assertSame('2026-10-01 00:00:00', $p->start->toDateTimeString());
    }

    public function test_this_month_compares_with_the_same_days_of_last_month(): void
    {
        $p = Period::from('this_month', $this->now());

        $this->assertSame('2026-09-01 00:00:00', $p->previousStart->toDateTimeString());
        $this->assertSame('2026-09-15 12:00:00', $p->previousEnd->toDateTimeString());
    }

    public function test_ninety_days_includes_today(): void
    {
        $p = Period::from('90d', $this->now());

        $this->assertSame('2026-07-18 00:00:00', $p->start->toDateTimeString());
        $this->assertSame('2026-10-15 12:00:00', $p->end->toDateTimeString());
    }

    public function test_a_past_calendar_month(): void
    {
        $p = Period::from('m-2026-09', $this->now());

        $this->assertSame('m-2026-09', $p->key);
        $this->assertSame('2026-09-01 00:00:00', $p->start->toDateTimeString());
        $this->assertSame('2026-09-30 23:59:59', $p->end->toDateTimeString());
        $this->assertSame('2026-08-01 00:00:00', $p->previousStart->toDateTimeString());
    }

    public function test_a_future_month_is_not_a_month(): void
    {
        $this->assertSame('this_month', Period::from('m-2027-01', $this->now())->key);
    }
}
