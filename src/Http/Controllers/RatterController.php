<?php

namespace RattingManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RattingManager\Http\Controllers\Concerns\ResolvesViewer;
use RattingManager\Services\Character\Players;
use RattingManager\Services\Configuration\Settings;
use RattingManager\Services\Ratting\Period;
use RattingManager\Services\Ratting\RattingMetrics;
use RattingManager\Services\Ratting\Scope;
use Seat\Web\Http\Controllers\Controller;

class RatterController extends Controller
{
    use ResolvesViewer;

    /** Every player who ratted in the last 90 days, to search and open. */
    /**
     * Every player who ratted in the period (the last 90 days unless one is
     * picked), biggest first, with how many have a wallet token on SeAT.
     */
    public function index(Request $request, RattingMetrics $metrics, Settings $settings)
    {
        $configured = $settings->configuredCorporations();
        $period = Period::from($request->query('period', '90d'));
        $corporations = $configured === [] ? [] : $this->pickedCorporations($request, $settings);
        $rows = $configured === []
            ? []
            : array_values($metrics->collect(new Scope($corporations), $period->start, $period->end)['players']);

        $ratted = app(\RattingManager\Services\Ratting\IncomeView::class)->isRatted();
        foreach ($rows as &$row) {
            $row['value'] = $ratted ? ($row['gross'] ?? 0.0) : $row['income'];
            $row['value_per_hour'] = $ratted ? ($row['gross_per_hour'] ?? 0.0) : $row['isk_per_hour'];
        }
        unset($row);

        usort($rows, fn ($a, $b) => $b['value'] <=> $a['value']);
        $rows = \RattingManager\Support\Corporations::markLeft($rows, $configured);

        $linked = count(array_filter($rows, fn ($r) => $r['wallet_token']));

        return view('ratting-manager::ratter.index', [
            'period' => $period,
            'brand' => \RattingManager\Support\Corporations::brand($corporations),
            'incomeMode' => $ratted ? 'ratted' : 'received',
            'rows' => $rows,
            'counts' => [
                'players' => count($rows),
                'characters' => array_sum(array_map(fn ($r) => count($r['characters']), $rows)),
                'linked' => $linked,
                'not_linked' => count($rows) - $linked,
            ],
            'notConfigured' => $configured === [],
        ]);
    }

    /** One player's ratting, as they see it on My Ratting, plus where each character's figures come from. */
    public function show(Request $request, int $characterId, Players $players, Settings $settings, DashboardController $dashboard)
    {
        $period = Period::from($request->query('period'));
        $characters = $players->charactersOfPlayer($characterId);
        $configured = $this->ratterCorporations($characters, $settings);
        $main = $players->mainOf($characterId);

        $personal = $dashboard->personal(new Scope($configured, $characters), $period, $request->query('month'));

        $kinds = DB::table('ratting_manager_ticks')
            ->whereIn('character_id', $characters)
            ->where('ticked_at', '>=', now()->subDays(90))
            ->groupBy('character_id', 'kind')
            ->selectRaw('character_id, kind, COUNT(*) as n')
            ->get();

        $current = DB::table('character_affiliations')->whereIn('character_id', $characters)->pluck('corporation_id', 'character_id');
        $corpNames = DB::table('corporation_infos')->whereIn('corporation_id', $current->values()->all() ?: [0])->pluck('name', 'corporation_id');

        $sources = [];
        foreach ($characters as $id) {
            $counts = ['paired' => 0, 'character' => 0, 'corporation' => 0];
            foreach ($kinds->where('character_id', $id) as $k) {
                $counts[$k->kind] = (int) $k->n;
            }
            $seenByCorp = $counts['paired'] + $counts['corporation'];
            $corp = $current[$id] ?? null;
            $sources[] = [
                'character_id' => $id,
                'name' => $players->name($id),
                'wallet_token' => $players->hasWalletToken($id),
                'counts' => $counts,
                'coverage' => $seenByCorp > 0 ? round($counts['paired'] / $seenByCorp * 100) : null,
                'corporation' => $corp ? ($corpNames[$corp] ?? (string) $corp) : null,
            ];
        }

        return view('ratting-manager::ratter.show', array_merge($personal, [
            'period' => $period,
            'brand' => \RattingManager\Support\Corporations::brand($configured),
            'left' => \RattingManager\Support\Corporations::markLeft([['main_id' => $main, 'characters' => $characters]], $settings->configuredCorporations())[0]['left'],
            'mainId' => $main,
            'mainName' => $players->name($main),
            'sources' => $sources,
            'characterId' => $characterId,
            'listUrl' => fn (string $list) => route('ratting-manager.ratters.list', ['characterId' => $characterId, 'list' => $list, 'period' => $period->key]),
        ]));
    }
}
