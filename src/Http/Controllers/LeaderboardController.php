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
     * Members see rank and hours for their own corporation, ranked by hours,
     * and nobody's ISK. Directors see every column, for any configured
     * corporation or all of them.
     */
    public function index(Request $request, RattingMetrics $metrics, Players $players, Settings $settings)
    {
        $period = Period::from($request->query('period'));
        $isDirector = Access::isDirector();
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
                'incomeMode' => $board['mode'],
                'median' => $board['median_isk_per_hour'],
                'totals' => $board['totals'],
                'ownMain' => $main,
                'corporations' => $this->corporationNames($settings),
                'pickedCorporation' => count($corporations) === 1 ? $corporations[0] : null,
            ]);
        }

        $board = $metrics->hoursBoard(new Scope($this->viewerCorporations($players, $settings)), $period, $main);

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
            'ownMain' => $main,
            'ownRank' => $board['own_rank'],
            'playerCount' => $board['players'],
        ]);
    }
}
