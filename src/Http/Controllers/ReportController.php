<?php

namespace RattingManager\Http\Controllers;

use Illuminate\Http\Request;
use RattingManager\Http\Controllers\Concerns\ResolvesViewer;
use RattingManager\Services\Configuration\Settings;
use RattingManager\Services\Ratting\ChartData;
use RattingManager\Services\Ratting\IncomeView;
use RattingManager\Services\Ratting\Period;
use RattingManager\Services\Ratting\RattingMetrics;
use RattingManager\Services\Ratting\Scope;
use Seat\Web\Http\Controllers\Controller;

/**
 * One calendar month the way the corporation reports it: ISK ratted (its
 * cut over its tax rate), ratting tax, alliance tax, active members, the
 * members ranked by ISK ratted and the top systems. Always ISK ratted,
 * whatever the dashboards are set to, so it can be compared with the
 * corporation's own monthly reports line by line.
 */
class ReportController extends Controller
{
    use ResolvesViewer;

    public const TOP_SYSTEMS = 10;

    public function index(Request $request, Settings $settings, RattingMetrics $metrics, ChartData $charts)
    {
        if ($settings->configuredCorporations() === []) {
            return view('ratting-manager::report.index', [
                'notConfigured' => true,
                'isAdmin' => \RattingManager\Support\Access::isAdmin(),
            ]);
        }

        return view('ratting-manager::report.index', $this->figures($request, $settings, $metrics, $charts) + [
            'notConfigured' => false,
            'choices' => Period::monthChoices(),
            'corporations' => $this->corporationNames($settings),
        ]);
    }

    /** The month's members as a CSV, the same rows the page shows. */
    public function csv(Request $request, Settings $settings, RattingMetrics $metrics, ChartData $charts)
    {
        abort_if($settings->configuredCorporations() === [], 404);

        $f = $this->figures($request, $settings, $metrics, $charts);
        $rows = [];
        foreach ($f['members'] as $i => $m) {
            $rows[] = [$i + 1, $m['name'], (float) $m['gross'], (float) $m['corp_tax'], round($m['minutes'] / 60, 2), count($m['characters'])];
        }
        $rows[] = ['', trans('ratting-manager::insights.total'), (float) ($f['totals']['gross'] ?? 0), (float) $f['totals']['corp_tax'], round($f['totals']['minutes'] / 60, 2), (int) $f['totals']['characters']];

        return \RattingManager\Support\Csv::download(
            'ratting-report-' . $f['period']->start->format('Y-m') . '.csv',
            ['#', trans('ratting-manager::report.member'), trans('ratting-manager::report.isk_ratted'), trans('ratting-manager::report.ratting_tax'), trans('ratting-manager::report.csv_hours'), trans('ratting-manager::report.csv_characters')],
            $rows
        );
    }

    /** The month's figures, for the page and for its CSV. */
    protected function figures(Request $request, Settings $settings, RattingMetrics $metrics, ChartData $charts): array
    {
        $period = self::month($request);
        $corporations = $this->pickedCorporations($request, $settings);
        $scope = new Scope($corporations);

        $board = $metrics->leaderboard($scope, $period, IncomeView::RATTED);

        return [
            'period' => $period,
            'pickedCorporation' => count($corporations) === 1 ? $corporations[0] : null,
            'totals' => $board['totals'],
            'members' => $board['rows'],
            'systems' => $charts->systems($scope, $period, self::TOP_SYSTEMS, IncomeView::RATTED),
            'space' => $charts->spaceCounts($scope, $period, IncomeView::RATTED),
        ];
    }

    /** The month asked for (?month=m-YYYY-MM), or the last full month. */
    public static function month(Request $request): Period
    {
        $choices = Period::monthChoices();
        $key = (string) $request->query('month', '');

        return Period::from(array_key_exists($key, $choices) ? $key : 'm-' . now('UTC')->subMonthNoOverflow()->format('Y-m'));
    }
}
