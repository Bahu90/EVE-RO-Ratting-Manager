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

    /** How many past months the picker offers, besides the rolling periods. */
    public const MONTHS = 24;

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

        // One calendar month, m-YYYY-MM, compared with the month before it.
        if ($key !== null && preg_match('/^m-(\d{4})-(\d{2})$/', $key, $m) && (int) $m[2] >= 1 && (int) $m[2] <= 12) {
            $start = Carbon::create((int) $m[1], (int) $m[2], 1, 0, 0, 0, 'UTC');
            if ($start->lte($now)) {
                $end = $start->copy()->endOfMonth();
                if ($end->gt($now)) {
                    $end = $now->copy();
                }
                $prevStart = $start->copy()->subMonthNoOverflow()->startOfMonth();

                return new self($key, $start, $end, $prevStart, $prevStart->copy()->endOfMonth());
            }
        }

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
        if (str_starts_with($this->key, 'm-')) {
            return $this->start->format('F Y');
        }

        return trans('ratting-manager::common.period_' . $this->key);
    }

    /**
     * The calendar months the picker offers, newest first.
     *
     * @return array<string, string> key (m-YYYY-MM) => label (September 2026)
     */
    public static function monthChoices(?Carbon $now = null): array
    {
        $month = ($now ?? Carbon::now('UTC'))->copy()->startOfMonth();
        $choices = [];
        for ($i = 0; $i < self::MONTHS; $i++) {
            $choices['m-' . $month->format('Y-m')] = $month->format('F Y');
            $month->subMonthNoOverflow();
        }

        return $choices;
    }

    public function cacheKey(): string
    {
        return $this->key . ':' . FigureCache::range($this->start, $this->end);
    }
}
