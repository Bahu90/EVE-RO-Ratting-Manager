<?php

namespace RattingManager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RattingManager\Services\Ratting\ActiveTime;
use RattingManager\Services\Ratting\ActiveTimeCounter;

/** Each tick stands for the 20 minutes before it, and overlaps count once. */
final class ActiveTimeTest extends TestCase
{
    private const T0 = 1_790_000_000;

    public function test_no_ticks_is_no_time(): void
    {
        $this->assertSame(0, ActiveTime::minutes([]));
    }

    public function test_one_tick_is_twenty_minutes(): void
    {
        $this->assertSame(20, ActiveTime::minutes([self::T0]));
    }

    public function test_back_to_back_ticks_add_up(): void
    {
        $ticks = [self::T0, self::T0 + 1200, self::T0 + 2400];

        $this->assertSame(60, ActiveTime::minutes($ticks));
    }

    public function test_alts_ticking_together_count_once(): void
    {
        // Three characters of one player, each paid every 20 minutes for an hour.
        $ticks = [];
        foreach ([0, 5, 9] as $offset) {
            foreach ([0, 1200, 2400] as $step) {
                $ticks[] = self::T0 + $step + $offset;
            }
        }

        // 60 minutes, plus the 9 seconds the last alt's ticks trail by, rounded.
        $this->assertSame(60, ActiveTime::minutes($ticks));
    }

    public function test_a_pause_is_not_counted(): void
    {
        // A tick, then one two hours later: two stretches of 20 minutes.
        $this->assertSame(40, ActiveTime::minutes([self::T0, self::T0 + 7200]));
    }

    public function test_an_early_tick_after_a_pause_counts_only_the_overlap_gap(): void
    {
        // The second tick comes 30 minutes after the first: 20 + 20.
        // The third 5 minutes after the second covers only 5 more.
        $this->assertSame(45, ActiveTime::minutes([self::T0, self::T0 + 1800, self::T0 + 2100]));
    }

    public function test_order_does_not_matter(): void
    {
        $ticks = [self::T0 + 2400, self::T0, self::T0 + 1200, self::T0 + 7200];

        $this->assertSame(ActiveTime::minutes([self::T0, self::T0 + 1200, self::T0 + 2400, self::T0 + 7200]), ActiveTime::minutes($ticks));
    }

    public function test_the_counter_matches_active_time(): void
    {
        $ticks = [self::T0, self::T0 + 300, self::T0 + 1500, self::T0 + 5000, self::T0 + 5100];
        $counter = new ActiveTimeCounter();
        foreach ($ticks as $t) {
            $counter->add($t);
        }

        $this->assertSame(ActiveTime::minutes($ticks), $counter->minutes());
    }
}
