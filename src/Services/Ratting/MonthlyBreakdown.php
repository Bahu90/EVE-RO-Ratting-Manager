<?php

namespace RattingManager\Services\Ratting;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RattingManager\Services\Character\Players;

/**
 * Month by month figures: per character for a member, per month for the
 * corporation.
 */
class MonthlyBreakdown
{
    private Players $players;

    public function __construct(Players $players)
    {
        $this->players = $players;
    }

    /** @return array<string, string> 'YYYY-MM' => 'October 2026', newest first, for a month picker */
    public static function monthChoices(int $months = 12): array
    {
        $choices = [];
        for ($m = now()->startOfMonth(), $i = 0; $i < $months; $i++, $m->subMonthNoOverflow()) {
            $choices[$m->format('Y-m')] = $m->format('F Y');
        }

        return $choices;
    }

    public static function month(?string $value): Carbon
    {
        $choices = self::monthChoices(24);

        return isset($choices[(string) $value])
            ? Carbon::parse($value . '-01')->startOfMonth()
            : now()->startOfMonth();
    }

    /**
     * What each character earned in a month, with the player's total. The
     * total's time counts alts ratting together once, so it can be less than
     * the characters' times added up.
     */
    public function byCharacter(Scope $scope, Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $money = $scope->apply(DB::table('ratting_manager_daily_summaries'))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('character_id')
            ->selectRaw('character_id, SUM(bounty_net) as bounty, SUM(ess_net) as ess, SUM(bounty_estimated + ess_estimated) as estimated, SUM(bounty_corp_tax + ess_corp_tax) as corp_tax, SUM(ticks) as ticks')
            ->get()
            ->keyBy('character_id');

        $ticks = $scope->apply(DB::table('ratting_manager_ticks'))
            ->whereBetween('ticked_at', [$start, $end])
            ->get(['character_id', 'ticked_at', 'kill_group', 'npc_kills']);

        $times = [];
        $groups = [];
        $allTimes = [];
        $allGroups = [];
        foreach ($ticks as $t) {
            $id = (int) $t->character_id;
            $ts = strtotime($t->ticked_at);
            $times[$id][] = $ts;
            $allTimes[] = $ts;
            if ($t->kill_group !== null) {
                $groups[$id][$t->kill_group] = (int) $t->npc_kills;
                $allGroups[$t->kill_group] = (int) $t->npc_kills;
            }
        }

        $ids = array_values(array_unique(array_merge($money->keys()->map(fn ($id) => (int) $id)->all(), array_keys($times))));
        $this->players->load($ids);

        $rows = [];
        foreach ($ids as $id) {
            $m = $money->get($id);
            $income = (float) ($m->bounty ?? 0) + (float) ($m->ess ?? 0);
            $minutes = ActiveTime::minutes($times[$id] ?? []);
            $rows[] = [
                'character_id' => $id,
                'name' => $this->players->name($id),
                'bounty' => round((float) ($m->bounty ?? 0), 2),
                'ess' => round((float) ($m->ess ?? 0), 2),
                'income' => round($income, 2),
                'estimated' => round((float) ($m->estimated ?? 0), 2),
                'corp_tax' => round((float) ($m->corp_tax ?? 0), 2),
                'ticks' => (int) ($m->ticks ?? 0),
                'minutes' => $minutes,
                'isk_per_hour' => $minutes > 0 ? round($income / ($minutes / 60), 2) : 0.0,
                'rats' => array_sum($groups[$id] ?? []),
            ];
        }
        usort($rows, fn ($a, $b) => $b['income'] <=> $a['income']);

        $total = [
            'bounty' => round(array_sum(array_column($rows, 'bounty')), 2),
            'ess' => round(array_sum(array_column($rows, 'ess')), 2),
            'income' => round(array_sum(array_column($rows, 'income')), 2),
            'estimated' => round(array_sum(array_column($rows, 'estimated')), 2),
            'corp_tax' => round(array_sum(array_column($rows, 'corp_tax')), 2),
            'ticks' => array_sum(array_column($rows, 'ticks')),
            'minutes' => ActiveTime::minutes($allTimes),
            'rats' => array_sum($allGroups),
        ];
        $total['isk_per_hour'] = $total['minutes'] > 0 ? round($total['income'] / ($total['minutes'] / 60), 2) : 0.0;

        return ['month' => $start->format('F Y'), 'rows' => $rows, 'total' => $total];
    }

    /**
     * Income per character for each of the last months, newest first, with
     * the month's total.
     */
    public function characterMatrix(Scope $scope, int $months = 12): array
    {
        $start = now()->subMonthsNoOverflow($months - 1)->startOfMonth();

        $rows = $scope->apply(DB::table('ratting_manager_daily_summaries'))
            ->where('date', '>=', $start->toDateString())
            ->groupBy('character_id', DB::raw("DATE_FORMAT(date, '%Y-%m')"))
            ->selectRaw("character_id, DATE_FORMAT(date, '%Y-%m') as month, SUM(bounty_net + ess_net) as income")
            ->get();

        $characters = $rows->pluck('character_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $this->players->load($characters);

        $byMonth = [];
        $byCharacter = [];
        foreach ($rows as $r) {
            $byMonth[$r->month][(int) $r->character_id] = round((float) $r->income, 2);
            $byCharacter[(int) $r->character_id] = ($byCharacter[(int) $r->character_id] ?? 0) + (float) $r->income;
        }
        arsort($byCharacter);

        $matrix = [];
        foreach (array_keys(self::monthChoices($months)) as $key) {
            $cells = $byMonth[$key] ?? [];
            $matrix[] = [
                'month' => Carbon::parse($key . '-01')->format('M Y'),
                'cells' => $cells,
                'total' => round(array_sum($cells), 2),
            ];
        }

        // Newest first; the months before the first ratting are left out.
        $active = array_keys(array_filter($matrix, fn ($m) => $m['cells'] !== []));
        $matrix = $active === [] ? [] : array_slice($matrix, 0, max($active) + 1);

        return [
            'characters' => array_map(fn ($id) => ['id' => $id, 'name' => $this->players->name($id)], array_keys($byCharacter)),
            'months' => $matrix,
        ];
    }

    /**
     * The corporation's last months: what its players earned, what landed in
     * the corporation's wallet, who ratted and for how long.
     */
    public function corporationMonths(Scope $scope, int $months = 12): array
    {
        $start = now()->subMonthsNoOverflow($months - 1)->startOfMonth();

        $money = $scope->apply(DB::table('ratting_manager_daily_summaries'))
            ->where('date', '>=', $start->toDateString())
            ->groupBy(DB::raw("DATE_FORMAT(date, '%Y-%m')"))
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as month, SUM(bounty_net) as bounty, SUM(ess_net) as ess, SUM(bounty_estimated + ess_estimated) as estimated, SUM(bounty_corp_tax + ess_corp_tax) as corp_tax")
            ->get()
            ->keyBy('month');

        $ticks = $scope->apply(DB::table('ratting_manager_ticks'))
            ->where('ticked_at', '>=', $start)
            ->get(['character_id', 'ticked_at']);
        $this->players->load($ticks->pluck('character_id')->map(fn ($id) => (int) $id)->unique()->all());

        $times = [];
        $characters = [];
        foreach ($ticks as $t) {
            $key = substr($t->ticked_at, 0, 7);
            $main = $this->players->mainOf((int) $t->character_id);
            $times[$key][$main][] = strtotime($t->ticked_at);
            $characters[$key][(int) $t->character_id] = true;
        }

        $rows = [];
        foreach (array_keys(self::monthChoices($months)) as $key) {
            $m = $money->get($key);
            $income = (float) ($m->bounty ?? 0) + (float) ($m->ess ?? 0);
            $tax = (float) ($m->corp_tax ?? 0);
            $minutes = array_sum(array_map([ActiveTime::class, 'minutes'], $times[$key] ?? []));
            $players = count($times[$key] ?? []);

            $rows[] = [
                'month' => Carbon::parse($key . '-01')->format('F Y'),
                'label' => Carbon::parse($key . '-01')->format('M Y'),
                'income' => round($income, 2),
                'bounty' => round((float) ($m->bounty ?? 0), 2),
                'ess' => round((float) ($m->ess ?? 0), 2),
                'estimated' => round((float) ($m->estimated ?? 0), 2),
                'corp_tax' => round($tax, 2),
                'tax_share' => ($income + $tax) > 0 ? round($tax / ($income + $tax) * 100, 1) : null,
                'players' => $players,
                'characters' => count($characters[$key] ?? []),
                'minutes' => $minutes,
                'isk_per_hour' => $minutes > 0 ? round($income / ($minutes / 60), 2) : 0.0,
                'income_per_player' => $players ? round($income / $players, 2) : 0.0,
            ];
        }

        return $rows;
    }
}
