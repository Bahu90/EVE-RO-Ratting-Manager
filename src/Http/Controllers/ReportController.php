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

    /**
     * The month as JSON, in the exact shape of the corporation's own
     * generate-ratting-report.mjs: month, overview, ratting_source, systems,
     * history (every day of the month) and members.
     *
     * Every money figure there is the corporation's cut ("bounty", "ess",
     * "ratting_tax") or that cut over the tax rate ("isk_ratted"), in whole
     * ISK. The cut comes from the same corporation journal rows the script
     * reads, already summed per day, character and system by the plugin;
     * ISK ratted uses each day's tax rate as the plugin knows it (15% for
     * EVE-RO, the script's constant).
     */
    public function json(Request $request, Settings $settings, \RattingManager\Services\Character\Players $players)
    {
        abort_if($settings->configuredCorporations() === [], 404);

        $period = self::month($request);
        $scope = new Scope($this->pickedCorporations($request, $settings));
        $from = $period->start->toDateString();
        $to = $period->start->copy()->endOfMonth()->toDateString();
        $base = fn () => $scope->apply(\Illuminate\Support\Facades\DB::table('ratting_manager_daily_summaries as d'), 'd')
            ->whereBetween('d.date', [$from, $to]);

        // History: every day of the month, 0 on days with no ratting.
        $byDay = $base()
            ->groupBy('d.date')
            ->selectRaw('d.date as day, SUM(d.bounty_corp_tax) as bounty, SUM(d.ess_corp_tax) as ess, SUM(d.bounty_gross + d.ess_gross) as gross')
            ->get()
            ->keyBy(fn ($r) => substr((string) $r->day, 0, 10));

        $history = [];
        $bountyTotal = 0.0;
        $essTotal = 0.0;
        for ($day = $period->start->copy()->startOfDay(); $day->toDateString() <= $to; $day->addDay()) {
            $r = $byDay->get($day->toDateString());
            $bounty = (float) ($r->bounty ?? 0);
            $ess = (float) ($r->ess ?? 0);
            $bountyTotal += $bounty;
            $essTotal += $ess;
            $history[] = [
                'day' => $day->toDateString(),
                'bounty' => (int) round($bounty),
                'ess' => (int) round($ess),
                'ratting_tax' => (int) round($bounty + $ess),
                'isk_ratted' => (int) round((float) ($r->gross ?? 0)),
            ];
        }

        // Systems: the corporation's cut per system, highest first.
        $systemRows = $base()
            ->join('solar_systems as s', 's.system_id', '=', 'd.solar_system_id')
            ->where('d.solar_system_id', '>', 0)
            ->groupBy('d.solar_system_id', 's.name', 's.constellation_id', 's.region_id')
            ->havingRaw('SUM(d.bounty_corp_tax + d.ess_corp_tax) > 0')
            ->orderByRaw('SUM(d.bounty_corp_tax + d.ess_corp_tax) DESC')
            ->get(['d.solar_system_id', 's.name', 's.constellation_id', 's.region_id', \Illuminate\Support\Facades\DB::raw('SUM(d.bounty_corp_tax + d.ess_corp_tax) as total')]);

        // Members: the corporation's cut per player (main and alts together), highest first.
        $characterRows = $base()
            ->groupBy('d.character_id')
            ->selectRaw('d.character_id, SUM(d.bounty_corp_tax) as bounty, SUM(d.ess_corp_tax) as ess, SUM(d.bounty_gross + d.ess_gross) as gross')
            ->get();
        $players->load($characterRows->pluck('character_id')->map(fn ($id) => (int) $id)->all());

        $byMain = [];
        foreach ($characterRows as $r) {
            $main = $players->mainOf((int) $r->character_id);
            $byMain[$main] ??= ['bounty' => 0.0, 'ess' => 0.0, 'gross' => 0.0];
            $byMain[$main]['bounty'] += (float) $r->bounty;
            $byMain[$main]['ess'] += (float) $r->ess;
            $byMain[$main]['gross'] += (float) $r->gross;
        }
        $players->load(array_keys($byMain));

        $members = [];
        foreach ($byMain as $main => $m) {
            if ($m['bounty'] + $m['ess'] <= 0) {
                continue;
            }
            $members[] = [
                'id' => $main,
                'name' => $players->name($main) ?: 'Unknown',
                'bounty' => (int) round($m['bounty']),
                'ess' => (int) round($m['ess']),
                'ratting_tax' => (int) round($m['bounty'] + $m['ess']),
                'isk_ratted' => (int) round($m['gross']),
            ];
        }
        usort($members, fn ($a, $b) => $b['ratting_tax'] <=> $a['ratting_tax']);

        $month = $period->start->format('Y-m');
        $output = [
            'month' => $month,
            'month_from' => $from,
            'month_to' => $to,
            'overview' => [
                'ratting_tax' => array_sum(array_column($history, 'ratting_tax')),
                'isk_ratted' => array_sum(array_column($history, 'isk_ratted')),
            ],
            'ratting_source' => [
                'bounty' => (int) round($bountyTotal),
                'ess' => (int) round($essTotal),
            ],
            'systems' => [
                'system_count' => $systemRows->count(),
                'constellation_count' => $systemRows->pluck('constellation_id')->unique()->count(),
                'region_count' => $systemRows->pluck('region_id')->unique()->count(),
                'top_systems' => $systemRows->take(self::TOP_SYSTEMS)->map(fn ($r) => [
                    'id' => (int) $r->solar_system_id,
                    'name' => $r->name,
                    'ratting_tax' => (int) round((float) $r->total),
                ])->values()->all(),
            ],
            'history' => $history,
            'members' => $members,
        ];

        // Two-space indentation and unescaped slashes and letters, as
        // JSON.stringify(output, null, 2) writes it.
        $json = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $json = preg_replace_callback('/^( +)/m', fn ($m) => str_repeat(' ', intdiv(strlen($m[1]), 2)), $json);

        return response($json, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $month . '.json"',
            'Cache-Control' => 'no-store',
        ]);
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
