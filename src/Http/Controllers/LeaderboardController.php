<?php

namespace RattingManager\Http\Controllers;

use Illuminate\Http\Request;
use RattingManager\Http\Controllers\Concerns\ResolvesViewer;
use RattingManager\Services\Character\Players;
use RattingManager\Services\Configuration\Settings;
use RattingManager\Services\Ratting\Period;
use RattingManager\Services\Ratting\RattingMetrics;
use RattingManager\Services\Ratting\Scope;
use RattingManager\Support\Access;
use Seat\Web\Http\Controllers\Controller;

class LeaderboardController extends Controller
{
    use ResolvesViewer;

    /**
     * The directors' leaderboard as a CSV: every player, both income
     * figures, ranked as the page ranks them. Directors only (route group).
     */
    public function csv(Request $request, RattingMetrics $metrics, Settings $settings)
    {
        abort_if($settings->configuredCorporations() === [], 404);

        $period = Period::from($request->query('period'));
        $board = $metrics->leaderboard(new Scope($this->pickedCorporations($request, $settings)), $period);

        $rows = [];
        foreach ($board['rows'] as $i => $r) {
            $rows[] = [
                $i + 1, $r['name'], count($r['characters']),
                (float) ($r['gross'] ?? 0), (float) $r['corp_tax'], (float) $r['income'],
                round($r['minutes'] / 60, 2), (float) $r['value_per_hour'],
                (int) $r['rats'], (int) $r['active_days'],
            ];
        }

        $t = fn (string $key) => trans('ratting-manager::' . $key);

        return \RattingManager\Support\Csv::download(
            'ratting-leaderboard-' . $period->start->format('Y-m-d') . '-' . $period->end->format('Y-m-d') . '.csv',
            [
                $t('leaderboard.rank'), $t('dashboard.player'), $t('report.csv_characters'),
                $t('dashboard.isk_ratted'), $t('dashboard.ratting_tax'), $t('insights.received'),
                $t('report.csv_hours'), $t('dashboard.isk_per_hour'), $t('dashboard.rats'), $t('dashboard.active_days'),
            ],
            $rows
        );
    }

    /**
     * Members see rank and hours for their own corporation, ranked by hours,
     * and nobody's ISK. Directors see every column, for any configured
     * corporation or all of them.
     */
    public function index(Request $request, RattingMetrics $metrics, Players $players, Settings $settings)
    {
        $period = Period::from($request->query('period'));
        // A director with no corporation to see gets the members' board.
        $isDirector = $this->seesCorporation($settings);
        $size = max(5, (int) $settings->get('leaderboard.size'));

        if ($settings->configuredCorporations() === []) {
            return view('ratting-manager::leaderboard.index', [
                'period' => $period,
                'notConfigured' => true,
                'isDirector' => $isDirector,
                'isAdmin' => Access::isAdmin(),
            ]);
        }

        $main = $this->viewerMain($players);

        if ($isDirector) {
            $corporations = $this->pickedCorporations($request, $settings);
            $board = $metrics->leaderboard(new Scope($corporations), $period);

            return view('ratting-manager::leaderboard.index', [
                'period' => $period,
                'notConfigured' => false,
                'isDirector' => true,
                'isAdmin' => Access::isAdmin(),
                'rows' => $board['rows'],
                'brand' => \RattingManager\Support\Corporations::brand($corporations),
                'taxRate' => \RattingManager\Support\Corporations::taxRate($corporations),
                'incomeMode' => $board['mode'],
                'median' => $board['median_isk_per_hour'],
                'totals' => $board['totals'],
                'ownMain' => $main,
                'corporations' => $this->corporationNames($settings),
                'pickedCorporation' => count($corporations) === 1 ? $corporations[0] : null,
            ]);
        }

        $memberCorporations = $this->viewerCorporations($players, $settings);
        $board = $metrics->hoursBoard(new Scope($memberCorporations), $period, $main);

        // The top of the board, and the member's own row when it is further down.
        $rows = array_slice($board['rows'], 0, $size);
        if ($board['own_rank'] !== null && $board['own_rank'] > $size) {
            $rows[] = $board['rows'][$board['own_rank'] - 1];
        }

        return view('ratting-manager::leaderboard.index', [
            'period' => $period,
            'notConfigured' => false,
            'isDirector' => false,
            'isAdmin' => false,
            'rows' => $rows,
            'brand' => \RattingManager\Support\Corporations::brand($memberCorporations),
            'ownMain' => $main,
            'ownRank' => $board['own_rank'],
            'playerCount' => $board['players'],
        ]);
    }
}
