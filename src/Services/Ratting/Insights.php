<?php

namespace RattingManager\Services\Ratting;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RattingManager\Services\Character\Players;
use RattingManager\Services\Npc\NpcRegistry;
use RattingManager\Services\Universe\SolarSystems;

/**
 * The analysis behind My Ratting, the ratter page and the Corporation tab:
 * where, when and how well people rat.
 *
 * Time is split the way it is counted everywhere else. Each tick stands for
 * the 20 minutes before it, a player's overlapping stretches count once, and
 * each tick is credited with the new time it adds. So a weekday, an hour or a
 * system gets exactly its part of the player's hours, and the parts add up
 * to the total.
 *
 * Income by weekday and hour is bounties only, by when they paid out. ESS
 * pays out hours later and in one lump, so putting it on the hour it arrived
 * would credit the wrong hour. ESS does count by system, ISK per hour there
 * included, since each payout names the system its bank was in.
 */
class Insights
{
    private const WEEKDAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    /** A system, weekday or hour window needs this much time before its ISK per hour counts as "best". */
    private const MIN_MINUTES_FOR_RATE = 60;

    private Players $players;
    private SolarSystems $systems;

    public function __construct(Players $players, SolarSystems $systems)
    {
        $this->players = $players;
        $this->systems = $systems;
    }

    public function for(Scope $scope, Period $period, bool $corporation): array
    {
        $key = 'rm:insights:' . $scope->cacheKey() . ':' . $period->cacheKey() . ':' . (int) $corporation;

        return Cache::remember($key, config('ratting-manager.cache_seconds', 300), function () use ($scope, $period, $corporation) {
            return $this->compute($scope, $period, $corporation);
        });
    }

    private function compute(Scope $scope, Period $period, bool $corporation): array
    {
        $ticks = $scope->apply(DB::table('ratting_manager_ticks'))
            ->whereBetween('ticked_at', [$period->start, $period->end])
            ->orderBy('ticked_at')
            ->get(['character_id', 'ticked_at', 'solar_system_id', 'net_amount', 'kill_group', 'npc_kills']);

        $this->players->load($ticks->pluck('character_id')->map(fn ($id) => (int) $id)->unique()->all());

        $span = ActiveTime::TICK_MINUTES * 60;
        $coveredUntil = [];

        $weekdaySeconds = array_fill(0, 7, 0);
        $weekdayBounty = array_fill(0, 7, 0.0);
        $hourSeconds = array_fill(0, 24, 0);
        $hourBounty = array_fill(0, 24, 0.0);
        $system = [];
        $groupsSeen = [];
        $rats = 0;
        $dates = [];
        $weekdayDates = array_fill(0, 7, []);
        $playersByDate = [];
        $playersBySlot = [];
        $playerSeconds = [];

        foreach ($ticks as $t) {
            $time = strtotime($t->ticked_at);
            $main = $this->players->mainOf((int) $t->character_id);
            $sys = (int) $t->solar_system_id;

            // The new time this tick adds to its player's ratting.
            $until = $coveredUntil[$main] ?? null;
            $start = $time - $span;
            if ($until === null || $start >= $until) {
                $added = $span;
            } elseif ($time > $until) {
                $added = $time - $until;
            } else {
                $added = 0;
            }
            $coveredUntil[$main] = max($until ?? $time, $time);

            $dow = (int) date('N', $time) - 1;
            $hour = (int) date('G', $time);
            $date = date('Y-m-d', $time);
            $net = (float) $t->net_amount;

            $weekdaySeconds[$dow] += $added;
            $weekdayBounty[$dow] += $net;
            $hourSeconds[$hour] += $added;
            $hourBounty[$hour] += $net;
            $playerSeconds[$main] = ($playerSeconds[$main] ?? 0) + $added;

            $system[$sys] ??= ['seconds' => 0, 'bounty' => 0.0, 'ess' => 0.0, 'ticks' => 0, 'rats' => 0, 'players' => [], 'dates' => [], 'last' => null, 'playerSeconds' => []];
            $system[$sys]['seconds'] += $added;
            $system[$sys]['bounty'] += $net;
            $system[$sys]['ticks']++;
            $system[$sys]['players'][$main] = true;
            $system[$sys]['dates'][$date] = true;
            $system[$sys]['last'] = $t->ticked_at;
            $system[$sys]['playerSeconds'][$main] = ($system[$sys]['playerSeconds'][$main] ?? 0) + $added;

            if ($t->kill_group !== null && !isset($groupsSeen[$t->kill_group])) {
                $groupsSeen[$t->kill_group] = true;
                $rats += (int) $t->npc_kills;
                $system[$sys]['rats'] += (int) $t->npc_kills;
            }

            $dates[$date] = true;
            $weekdayDates[$dow][$date] = true;
            $playersByDate[$date][$main] = true;
            $playersBySlot[date('Y-m-d H', $time)][$main] = true;
        }

        $ess = $scope->apply(DB::table('ratting_manager_ess_payouts'))
            ->whereBetween('paid_at', [$period->start, $period->end])
            ->groupBy('solar_system_id')
            ->selectRaw('solar_system_id, SUM(net_amount) as net')
            ->pluck('net', 'solar_system_id');
        foreach ($ess as $sys => $net) {
            $sys = (int) $sys;
            $system[$sys] ??= ['seconds' => 0, 'bounty' => 0.0, 'ess' => 0.0, 'ticks' => 0, 'rats' => 0, 'players' => [], 'dates' => [], 'last' => null, 'playerSeconds' => []];
            $system[$sys]['ess'] += (float) $net;
        }

        $totalSeconds = array_sum($playerSeconds);
        $totalBounty = array_sum($weekdayBounty);
        $totalEss = (float) $ess->sum();
        $totalIncome = $totalBounty + $totalEss;

        $systems = $this->systemRows($system, $totalSeconds, $totalIncome);
        $weekdays = $this->weekdayRows($weekdaySeconds, $weekdayBounty, $weekdayDates, $playersByDate);
        $hours = $this->hourRows($hourSeconds, $hourBounty, $playersBySlot);
        $sessions = $this->sessions($scope, $period);
        $npcs = $this->topNpcs($scope, $period);

        $highlights = [
            'favourite_system' => $this->pick($systems, 'minutes'),
            'best_system' => $this->pick(array_filter($systems, fn ($s) => $s['minutes'] >= self::MIN_MINUTES_FOR_RATE && $s['system_id'] > 0), 'isk_per_hour'),
            'busiest_weekday' => $this->pick($weekdays, 'minutes'),
            'best_weekday' => $this->pick(array_filter($weekdays, fn ($w) => $w['minutes'] >= self::MIN_MINUTES_FOR_RATE), 'isk_per_hour'),
            'busiest_window' => $this->bestWindow($hourSeconds, $hourBounty, false),
            'best_window' => $this->bestWindow($hourSeconds, $hourBounty, true),
            'favourite_faction' => $npcs['favourite_faction'],
            'rats_per_hour' => $totalSeconds > 0 ? round($rats / ($totalSeconds / 3600), 1) : 0.0,
            'ess_share' => $totalIncome > 0 ? round($totalEss / $totalIncome * 100) : null,
            'active_days' => count($dates),
            'income_per_active_day' => count($dates) ? round($totalIncome / count($dates), 2) : 0.0,
            'sessions' => $sessions,
        ];

        if ($corporation) {
            $peak = null;
            foreach ($playersBySlot as $slot => $set) {
                if ($peak === null || count($set) > $peak['players']) {
                    $peak = ['players' => count($set), 'when' => $slot . ':00'];
                }
            }
            $highlights['peak_concurrent'] = $peak;
            $highlights['players_per_day'] = count($playersByDate)
                ? round(array_sum(array_map('count', $playersByDate)) / count($playersByDate), 1)
                : 0.0;
            $highlights['income_per_player'] = count($playerSeconds) ? round($totalIncome / count($playerSeconds), 2) : 0.0;
        } else {
            $highlights += $this->streaks(array_keys($dates));
        }

        return [
            'highlights' => $highlights,
            'systems' => $systems,
            'weekdays' => $weekdays,
            'hours' => $hours,
            'npcs' => $npcs['top'],
            'totals' => [
                'minutes' => intdiv($totalSeconds + 30, 60),
                'bounty' => round($totalBounty, 2),
                'ess' => round($totalEss, 2),
                'income' => round($totalIncome, 2),
                'rats' => $rats,
            ],
        ];
    }

    private function systemRows(array $system, int $totalSeconds, float $totalIncome): array
    {
        $details = $this->systems->details(array_filter(array_keys($system)));

        $rows = [];
        foreach ($system as $id => $s) {
            $income = $s['bounty'] + $s['ess'];
            $minutes = intdiv($s['seconds'] + 30, 60);

            $topMain = null;
            if ($s['playerSeconds']) {
                arsort($s['playerSeconds']);
                $topMain = (int) array_key_first($s['playerSeconds']);
            }

            $rows[] = [
                'system_id' => $id,
                'name' => $id ? ($details[$id]['name'] ?? 'System #' . $id) : trans('ratting-manager::common.system_not_given'),
                'security' => $id ? ($details[$id]['security'] ?? null) : null,
                'minutes' => $minutes,
                'income' => round($income, 2),
                'bounty' => round($s['bounty'], 2),
                'ess' => round($s['ess'], 2),
                'isk_per_hour' => $minutes > 0 ? round($income / ($minutes / 60), 2) : 0.0,
                'ticks' => $s['ticks'],
                'rats' => $s['rats'],
                'players' => count($s['players']),
                'active_days' => count($s['dates']),
                'last' => $s['last'],
                'top_main' => $topMain,
                'hours_share' => $totalSeconds > 0 ? round($s['seconds'] / $totalSeconds * 100, 1) : 0.0,
                'income_share' => $totalIncome > 0 ? round($income / $totalIncome * 100, 1) : 0.0,
            ];
        }

        usort($rows, fn ($a, $b) => [$b['minutes'], $b['income']] <=> [$a['minutes'], $a['income']]);

        return $rows;
    }

    private function weekdayRows(array $seconds, array $bounty, array $weekdayDates, array $playersByDate): array
    {
        $rows = [];
        foreach (self::WEEKDAYS as $i => $name) {
            $minutes = intdiv($seconds[$i] + 30, 60);
            $days = count($weekdayDates[$i]);
            $players = 0;
            foreach (array_keys($weekdayDates[$i]) as $date) {
                $players += count($playersByDate[$date] ?? []);
            }

            $rows[] = [
                'index' => $i,
                'name' => $name,
                'short' => substr($name, 0, 3),
                'minutes' => $minutes,
                'bounty' => round($bounty[$i], 2),
                'isk_per_hour' => $minutes > 0 ? round($bounty[$i] / ($minutes / 60), 2) : 0.0,
                'active_days' => $days,
                'players_per_day' => $days ? round($players / $days, 1) : 0.0,
            ];
        }

        return $rows;
    }

    private function hourRows(array $seconds, array $bounty, array $playersBySlot): array
    {
        $playersByHour = array_fill(0, 24, 0);
        foreach ($playersBySlot as $slot => $set) {
            $playersByHour[(int) substr($slot, 11, 2)] += count($set);
        }

        $rows = [];
        for ($h = 0; $h < 24; $h++) {
            $minutes = intdiv($seconds[$h] + 30, 60);
            $rows[] = [
                'hour' => $h,
                'label' => sprintf('%02d:00', $h),
                'minutes' => $minutes,
                'bounty' => round($bounty[$h], 2),
                'isk_per_hour' => $minutes > 0 ? round($bounty[$h] / ($minutes / 60), 2) : 0.0,
                'player_hours' => $playersByHour[$h],
            ];
        }

        return $rows;
    }

    /**
     * The three-hour stretch of the day, in EVE time, with the most ratting,
     * or with the best bounty ISK per hour. Wraps past midnight.
     */
    private function bestWindow(array $seconds, array $bounty, bool $byRate): ?array
    {
        $best = null;
        for ($h = 0; $h < 24; $h++) {
            $s = $seconds[$h] + $seconds[($h + 1) % 24] + $seconds[($h + 2) % 24];
            $b = $bounty[$h] + $bounty[($h + 1) % 24] + $bounty[($h + 2) % 24];
            $minutes = intdiv($s + 30, 60);

            if ($minutes === 0 || ($byRate && $minutes < self::MIN_MINUTES_FOR_RATE)) {
                continue;
            }

            $rate = round($b / ($minutes / 60), 2);
            $score = $byRate ? $rate : $minutes;
            if ($best === null || $score > $best['score']) {
                $best = [
                    'score' => $score,
                    'label' => sprintf('%02d:00-%02d:00', $h, ($h + 3) % 24),
                    'minutes' => $minutes,
                    'isk_per_hour' => $rate,
                ];
            }
        }

        if ($best) {
            unset($best['score']);
        }

        return $best;
    }

    /** Session habits: how many, how long, and the longest. */
    private function sessions(Scope $scope, Period $period): array
    {
        $minutes = $scope->apply(DB::table('ratting_manager_sessions'))
            ->whereBetween('started_at', [$period->start->copy()->subMinutes(ActiveTime::TICK_MINUTES), $period->end])
            ->orderBy('minutes')
            ->pluck('minutes')
            ->map(fn ($m) => (int) $m)
            ->all();

        $count = count($minutes);

        return [
            'count' => $count,
            'median_minutes' => $count ? $minutes[intdiv($count, 2)] : 0,
            'longest_minutes' => $count ? max($minutes) : 0,
        ];
    }

    /** The NPCs killed most often, a fleet's shared payout counted once. */
    private function topNpcs(Scope $scope, Period $period): array
    {
        $base = $scope->apply(DB::table('ratting_manager_ticks as t'), 't')
            ->join('ratting_manager_tick_kills as k', 'k.tick_id', '=', 't.id')
            ->whereBetween('t.ticked_at', [$period->start, $period->end])
            ->whereNotNull('t.kill_group')
            ->select(['t.kill_group', 'k.npc_type_id', 'k.quantity'])
            ->distinct();

        $rows = DB::query()->fromSub($base, 'x')
            ->leftJoin('ratting_manager_npc_types as n', 'n.type_id', '=', 'x.npc_type_id')
            ->groupBy('x.npc_type_id', 'n.name', 'n.faction', 'n.size', 'n.rank')
            ->selectRaw('x.npc_type_id, n.name, n.faction, n.size, n.rank, SUM(x.quantity) as killed')
            ->orderByDesc('killed')
            ->get();

        $byFaction = [];
        foreach ($rows as $r) {
            $faction = $r->faction ?? 'other';
            $byFaction[$faction] = ($byFaction[$faction] ?? 0) + (int) $r->killed;
        }
        arsort($byFaction);
        $favourite = array_key_first($byFaction);

        return [
            'top' => $rows->take(10)->map(fn ($r) => [
                'type_id' => (int) $r->npc_type_id,
                'name' => $r->name ?? ('Type ' . $r->npc_type_id),
                'faction' => $r->faction ?? 'other',
                'size' => $r->size ?? 'unknown',
                'rank' => $r->rank ?? 'regular',
                'killed' => (int) $r->killed,
            ])->all(),
            'favourite_faction' => $favourite === null ? null : [
                'key' => $favourite,
                'name' => NpcRegistry::FACTIONS[$favourite] ?? ucfirst($favourite),
                'killed' => $byFaction[$favourite],
            ],
        ];
    }

    /**
     * Days in a row with at least one tick: the longest run in the period,
     * and the run still going, which counts today or yesterday as current.
     *
     * @param string[] $dates
     */
    private function streaks(array $dates): array
    {
        if ($dates === []) {
            return ['longest_streak' => 0, 'current_streak' => 0];
        }

        sort($dates);
        $longest = 1;
        $run = 1;
        for ($i = 1; $i < count($dates); $i++) {
            $run = Carbon::parse($dates[$i - 1])->addDay()->toDateString() === $dates[$i] ? $run + 1 : 1;
            $longest = max($longest, $run);
        }

        $last = end($dates);
        $current = in_array($last, [now()->toDateString(), now()->subDay()->toDateString()], true) ? $run : 0;

        return ['longest_streak' => $longest, 'current_streak' => $current];
    }

    private function pick(array $rows, string $field): ?array
    {
        $best = null;
        foreach ($rows as $row) {
            if (($row[$field] ?? 0) > 0 && ($best === null || $row[$field] > $best[$field])) {
                $best = $row;
            }
        }

        return $best;
    }
}
