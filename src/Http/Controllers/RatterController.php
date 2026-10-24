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
    public function index(RattingMetrics $metrics, Settings $settings)
    {
        $configured = $settings->configuredCorporations();
        $rows = $configured === []
            ? []
            : array_values($metrics->collect(new Scope($configured), now()->subDays(89)->startOfDay(), now())['players']);

        usort($rows, fn ($a, $b) => strcmp((string) $b['last_tick'], (string) $a['last_tick']));

        return view('ratting-manager::ratter.index', [
            'rows' => $rows,
            'notConfigured' => $configured === [],
        ]);
    }

    /** One player's ratting, as they see it on My Ratting, plus where each character's figures come from. */
    public function show(Request $request, int $characterId, Players $players, Settings $settings, DashboardController $dashboard)
    {
        $configured = $settings->configuredCorporations();
        $period = Period::from($request->query('period'));
        $characters = $players->charactersOfPlayer($characterId);
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
            'mainId' => $main,
            'mainName' => $players->name($main),
            'sources' => $sources,
            'characterId' => $characterId,
            'listUrl' => fn (string $list) => route('ratting-manager.ratters.list', ['characterId' => $characterId, 'list' => $list, 'period' => $period->key]),
        ]));
    }
}
