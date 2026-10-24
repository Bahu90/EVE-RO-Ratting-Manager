<?php

namespace RattingManager\Services\Ratting;

/**
 * How long somebody was ratting, from their tick times.
 *
 * A tick pays for the kills of the 20 minutes before it, so each tick stands
 * for that stretch. Overlapping stretches count once: three alts ratting
 * together for an hour are one hour of the player's time, and a tick that
 * came early after a pause does not count the pause.
 */
final class ActiveTime
{
    public const TICK_MINUTES = 20;

    /** @param iterable<int> $timestamps unix seconds, any order */
    public static function minutes(iterable $timestamps): int
    {
        $times = [];
        foreach ($timestamps as $t) {
            $times[] = (int) $t;
        }

        if ($times === []) {
            return 0;
        }

        sort($times);
        $span = self::TICK_MINUTES * 60;
        $seconds = 0;
        $coveredUntil = null;

        foreach ($times as $t) {
            $start = $t - $span;
            if ($coveredUntil === null || $start >= $coveredUntil) {
                $seconds += $span;
            } elseif ($t > $coveredUntil) {
                $seconds += $t - $coveredUntil;
            }
            $coveredUntil = max($coveredUntil ?? $t, $t);
        }

        return intdiv($seconds + 30, 60);
    }
}
