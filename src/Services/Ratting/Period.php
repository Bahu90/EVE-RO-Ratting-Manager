<?php

namespace RattingManager\Services\Ratting;

use Carbon\Carbon;

/**
 * The stretch of time a page is showing, with the stretch before it of the
 * same length for the change column. Everything is in EVE time.
 */
final class Period
{
    public const CHOICES = ['this_month', 'last_month', '30d', '90d', '12m'];

    public string $key;
    public Carbon $start;
    public Carbon $end;
    public Carbon $previousStart;
    public Carbon $previousEnd;

    private function __construct(string $key, Carbon $start, Carbon $end, Carbon $previousStart, Carbon $previousEnd)
    {
        $this->key = $key;
        $this->start = $start;
        $this->end = $end;
        $this->previousStart = $previousStart;
        $this->previousEnd = $previousEnd;
    }

    public static function from(?string $key, ?Carbon $now = null): self
    {
        $now = ($now ?? Carbon::now('UTC'))->copy();
        $key = in_array($key, self::CHOICES, true) ? $key : 'this_month';

        switch ($key) {
            case 'last_month':
                $start = $now->copy()->subMonthNoOverflow()->startOfMonth();
                $end = $start->copy()->endOfMonth();
                $prevStart = $start->copy()->subMonthNoOverflow()->startOfMonth();
                return new self($key, $start, $end, $prevStart, $prevStart->copy()->endOfMonth());

            case '30d':
            case '90d':
                $days = $key === '30d' ? 30 : 90;
                $start = $now->copy()->subDays($days - 1)->startOfDay();
                $prevEnd = $start->copy()->subSecond();
                return new self($key, $start, $now, $prevEnd->copy()->subDays($days - 1)->startOfDay(), $prevEnd);

            case '12m':
                $start = $now->copy()->subMonthsNoOverflow(11)->startOfMonth();
                $prevEnd = $start->copy()->subSecond();
                return new self($key, $start, $now, $prevEnd->copy()->subMonthsNoOverflow(11)->startOfMonth(), $prevEnd);

            default:
                $start = $now->copy()->startOfMonth();
                $prevStart = $start->copy()->subMonthNoOverflow()->startOfMonth();
                // Compare like with like: the first N days of last month.
                $prevEnd = $prevStart->copy()->addSeconds($now->getTimestamp() - $start->getTimestamp());
                return new self('this_month', $start, $now, $prevStart, min($prevEnd, $prevStart->copy()->endOfMonth()));
        }
    }

    /** A week from Monday 00:00 to Sunday 23:59:59 EVE time, with the week before it. */
    public static function week(Carbon $anyDay): self
    {
        $start = $anyDay->copy()->startOfWeek(Carbon::MONDAY);
        $end = $start->copy()->endOfWeek(Carbon::SUNDAY);
        $prevStart = $start->copy()->subWeek();

        return new self('week', $start, $end, $prevStart, $prevStart->copy()->endOfWeek(Carbon::SUNDAY));
    }

    /** A calendar month, with the month before it. */
    public static function month(Carbon $anyDay): self
    {
        $start = $anyDay->copy()->startOfMonth();
        $prevStart = $start->copy()->subMonthNoOverflow()->startOfMonth();

        return new self('month', $start, $start->copy()->endOfMonth(), $prevStart, $prevStart->copy()->endOfMonth());
    }

    public function label(): string
    {
        return trans('ratting-manager::common.period_' . $this->key);
    }

    public function cacheKey(): string
    {
        return $this->key . ':' . FigureCache::range($this->start, $this->end);
    }
}
