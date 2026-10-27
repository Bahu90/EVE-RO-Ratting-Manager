{{-- The Corporation tab, rendered on request and dropped into the dashboard. Directors only.
    The headline figures stay on top; everything else is split into four
    sub-tabs so the tab opens on the overview instead of a long scroll.
    Charts in a closed sub-tab are drawn when it opens (ratting-charts.js). --}}

@php
    $rmRatted = ($incomeMode ?? 'received') === 'ratted';
    $rmTaxTotal = (float) ($totals['bounty_tax'] ?? 0) + (float) ($totals['ess_tax'] ?? 0);
    $rmBountyShare = $rmTaxTotal > 0 ? round(($totals['bounty_tax'] ?? 0) / $rmTaxTotal * 100) : 0;
@endphp
{{-- The hero: the headline figures, where the corporation's cut came from,
     and where the corporation ratted, side by side as on the ratting page. --}}
<div class="row rm-hero">
    <div class="col-xl-4 col-lg-6">
        <div class="row">
            <div class="col-sm-6">
                <div class="info-box">
                    <div class="info-box-content">
                        @if($rmRatted)
                        <span class="info-box-text" title="{{ trans('ratting-manager::dashboard.isk_ratted_title') }}">{{ trans('ratting-manager::dashboard.isk_ratted') }}</span>
                        <span class="info-box-number">@rmisk($totals['gross'])</span>
                        <small>{{ trans('ratting-manager::dashboard.received_by_pilots', ['amount' => \RattingManager\Support\Format::isk($totals['income'])]) }}</small>
                        @else
                        <span class="info-box-text">{{ trans('ratting-manager::dashboard.ratting_income') }}</span>
                        <span class="info-box-number">@rmisk($totals['income'])</span>
                        @if($totals['estimated'] > 0)
                            <small class="rm-estimated" title="{{ trans('ratting-manager::common.worked_out_title') }}">{{ trans('ratting-manager::dashboard.worked_out_share', ['amount' => \RattingManager\Support\Format::isk($totals['estimated'])]) }}</small>
                        @else
                            <small>{{ trans('ratting-manager::dashboard.ratting_income_hint') }}</small>
                        @endif
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="info-box rm-tile-tax">
                    <div class="info-box-content">
                        <span class="info-box-text" title="{{ trans('ratting-manager::dashboard.ratting_tax_title') }}">{{ trans('ratting-manager::dashboard.ratting_tax') }}</span>
                        <span class="info-box-number">@rmisk($totals['corp_tax'])</span>
                        <small>{{ trans('ratting-manager::dashboard.corp_tax_hint') }}</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="info-box">
                    <div class="info-box-content">
                        <span class="info-box-text">{{ trans('ratting-manager::dashboard.active_players') }}</span>
                        <span class="info-box-number">{{ number_format($totals['players']) }}</span>
                        <small>{{ trans('ratting-manager::dashboard.members_sub', ['count' => number_format($totals['characters'])]) }}</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                @if(($totals['alliance_rate'] ?? 0) > 0)
                <div class="info-box rm-tile-warn">
                    <div class="info-box-content">
                        <span class="info-box-text" title="{{ trans('ratting-manager::dashboard.alliance_tax_title') }}">{{ trans('ratting-manager::dashboard.alliance_tax') }}</span>
                        <span class="info-box-number">@rmisk($totals['alliance_tax'])</span>
                        <small>{{ trans('ratting-manager::dashboard.alliance_tax_rate', ['rate' => rtrim(rtrim(number_format($totals['alliance_rate'] * 100, 2), '0'), '.')]) }}</small>
                    </div>
                </div>
                @else
                <div class="info-box">
                    <div class="info-box-content">
                        <span class="info-box-text">{{ trans('ratting-manager::dashboard.hours_ratted') }}</span>
                        <span class="info-box-number">{{ \RattingManager\Support\Format::hours((int) $totals['minutes']) }}</span>
                        <small title="{{ trans('ratting-manager::dashboard.median_hint') }}">{{ trans('ratting-manager::dashboard.hours_corp_sub', ['rate' => \RattingManager\Support\Format::isk($median)]) }}</small>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-lg-6">
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title">{{ trans('ratting-manager::dashboard.tax_split') }}</h3>
            </div>
            <div class="card-body">
                @if($rmTaxTotal > 0)
                <div class="rm-donut">
                    <canvas id="rm-corp-taxsplit" aria-label="{{ trans('ratting-manager::dashboard.tax_split') }}"></canvas>
                    <div class="rm-donut-center">
                        <strong>@rmisk($rmTaxTotal)</strong>
                        <span>{{ mb_strtoupper(trans('ratting-manager::dashboard.ratting_tax')) }}</span>
                    </div>
                </div>
                <div class="rm-legend">
                    <span><i style="background: var(--rm-series-1)"></i> {{ trans('ratting-manager::insights.bounties') }} {{ $rmBountyShare }}%</span>
                    <span><i style="background: var(--rm-series-2)"></i> {{ trans('ratting-manager::insights.ess') }} {{ 100 - $rmBountyShare }}%</span>
                </div>
                @else
                <p class="text-muted mb-0">{{ trans('ratting-manager::dashboard.tax_split_empty') }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-lg-12">
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title">{{ trans('ratting-manager::dashboard.where_we_ratted') }}</h3>
                <div class="card-tools">
                    <a href="#rm-corp-sub-space" class="btn btn-sm btn-outline-secondary rm-open-subtab" data-subtab="#rm-corp-sub-space-tab">{{ trans('ratting-manager::dashboard.sub_space') }}</a>
                </div>
            </div>
            <div class="card-body">
                @isset($space)
                <div class="rm-mini-stats mb-3">
                    <div class="rm-mini-stat"><span>{{ mb_strtoupper(trans('ratting-manager::dashboard.systems')) }}</span><span>{{ number_format($space['systems']) }}</span></div>
                    <div class="rm-mini-stat"><span>{{ mb_strtoupper(trans('ratting-manager::dashboard.constellations_short')) }}</span><span>{{ number_format($space['constellations']) }}</span></div>
                    <div class="rm-mini-stat"><span>{{ mb_strtoupper(trans('ratting-manager::dashboard.regions')) }}</span><span>{{ number_format($space['regions']) }}</span></div>
                </div>
                @endisset
                @if(!empty($topSystems))
                <div class="rm-eyebrow mb-2">{{ trans('ratting-manager::dashboard.top_systems') }}</div>
                <div class="rm-top-systems">
                    @foreach($topSystems as $system)
                        <div><span>{{ $system['name'] }}</span><span>{{ \RattingManager\Support\Format::isk($system['value']) }}</span></div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-pills rm-subtabs mb-3" id="rm-corp-subtabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="rm-corp-sub-overview-tab" data-toggle="pill" href="#rm-corp-sub-overview" role="tab" aria-controls="rm-corp-sub-overview" aria-selected="true">
            <i class="fas fa-trophy"></i> {{ trans('ratting-manager::dashboard.sub_overview') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="rm-corp-sub-activity-tab" data-toggle="pill" href="#rm-corp-sub-activity" role="tab" aria-controls="rm-corp-sub-activity" aria-selected="false">
            <i class="fas fa-chart-line"></i> {{ trans('ratting-manager::dashboard.sub_activity') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="rm-corp-sub-space-tab" data-toggle="pill" href="#rm-corp-sub-space" role="tab" aria-controls="rm-corp-sub-space" aria-selected="false">
            <i class="fas fa-globe"></i> {{ trans('ratting-manager::dashboard.sub_space') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="rm-corp-sub-members-tab" data-toggle="pill" href="#rm-corp-sub-members" role="tab" aria-controls="rm-corp-sub-members" aria-selected="false">
            <i class="fas fa-users"></i> {{ trans('ratting-manager::dashboard.sub_members') }}
        </a>
    </li>
</ul>

<div class="tab-content" id="rm-corp-subtab-content">
    <div class="tab-pane fade show active" id="rm-corp-sub-overview" role="tabpanel" aria-labelledby="rm-corp-sub-overview-tab">
    <div class="row">
        <div class="col-12">
            <div class="card card-dark">
                <div class="card-header">
                    <h3 class="card-title">{{ trans('ratting-manager::dashboard.daily_income_and_tax') }}</h3>
                    <div class="card-tools rm-legend mt-0">
                        <span><i style="background: var(--rm-series-1)"></i> {{ trans('ratting-manager::dashboard.series_income') }}</span>
                        <span><i style="background: var(--rm-series-3)"></i> {{ trans('ratting-manager::dashboard.ratting_tax') }}</span>
                    </div>
                </div>
                <div class="card-body"><div class="rm-chart rm-chart-wide"><canvas id="rm-corp-daily"></canvas></div><p class="rm-chart-note">{{ trans('ratting-manager::dashboard.daily_income_note') }}</p></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            @include('ratting-manager::partials.insights', ['insights' => $insights, 'corporation' => true])
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card card-dark">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-trophy"></i> {{ trans('ratting-manager::dashboard.leaderboard') }}</h3>
                    <div class="card-tools">
                        @include('ratting-manager::partials.view-all', ['count' => count($leaderboard), 'limit' => \RattingManager\Http\Controllers\ListController::DASHBOARD_ROWS, 'url' => route('ratting-manager.leaderboard', $listQuery)])
                    </div>
                </div>
                <div class="card-body p-0">
                    @include('ratting-manager::leaderboard.partials.director-table', ['rows' => $leaderboard, 'ownMain' => null, 'limit' => \RattingManager\Http\Controllers\ListController::DASHBOARD_ROWS])
                </div>
            </div>
        </div>
    </div>
    </div>
    <div class="tab-pane fade" id="rm-corp-sub-activity" role="tabpanel" aria-labelledby="rm-corp-sub-activity-tab">
    <div class="row">
        <div class="col-lg-8">
            <div class="card card-dark">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-bar"></i> {{ trans('ratting-manager::dashboard.income_12_months') }}</h3>
                </div>
                <div class="card-body"><div class="rm-chart"><canvas id="rm-corp-monthly"></canvas></div><p class="rm-chart-note">{{ trans('ratting-manager::dashboard.income_12_months_note') }}</p></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-dark">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-pie"></i> {{ trans('ratting-manager::dashboard.concentration') }}</h3>
                </div>
                <div class="card-body"><div class="rm-chart"><canvas id="rm-corp-concentration"></canvas></div><p class="rm-chart-note">{{ trans('ratting-manager::dashboard.concentration_note') }}</p></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            @include('ratting-manager::dashboard.partials.corporation-months', ['months' => $months])
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card card-dark">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-th"></i> {{ trans('ratting-manager::dashboard.when_corp_rats') }}</h3>
                </div>
                <div class="card-body">
                    @include('ratting-manager::partials.heatmap', ['heatmap' => $heatmap, 'perPlayer' => true, 'title' => trans('ratting-manager::dashboard.when_corp_rats')])
                    <p class="rm-chart-note">{{ trans('ratting-manager::dashboard.when_corp_rats_note') }}</p>
                </div>
            </div>
        </div>
    </div>
    </div>
    <div class="tab-pane fade" id="rm-corp-sub-space" role="tabpanel" aria-labelledby="rm-corp-sub-space-tab">
    <div class="row">
        <div class="col-12">
            @include('ratting-manager::partials.systems-table', ['rows' => $insights['systems'], 'corporation' => true, 'limit' => \RattingManager\Http\Controllers\ListController::DASHBOARD_ROWS, 'moreUrl' => $listUrl('systems')])
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            @include('ratting-manager::partials.npc-table', ['npcs' => $insights['npcs'], 'rats' => $totals['rats']])
        </div>
    </div>
    </div>
    <div class="tab-pane fade" id="rm-corp-sub-members" role="tabpanel" aria-labelledby="rm-corp-sub-members-tab">
    <div class="row">
        <div class="col-12">
            <div class="card card-dark">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-exchange-alt"></i> {{ trans('ratting-manager::dashboard.ratter_movement') }}</h3>
                </div>
                <div class="card-body"><div class="rm-chart"><canvas id="rm-corp-movement"></canvas></div><p class="rm-chart-note">{{ trans('ratting-manager::dashboard.ratter_movement_note') }}</p></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            @include('ratting-manager::dashboard.partials.gone-quiet', ['rows' => $goneQuiet, 'limit' => \RattingManager\Http\Controllers\ListController::DASHBOARD_ROWS, 'moreUrl' => $listUrl('gone-quiet')])
        </div>
        <div class="col-lg-6">
            @include('ratting-manager::dashboard.partials.untracked', ['rows' => $untracked, 'limit' => \RattingManager\Http\Controllers\ListController::DASHBOARD_ROWS, 'moreUrl' => $listUrl('untracked')])
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            @include('ratting-manager::dashboard.partials.spawns', ['spawns' => $spawns, 'total' => $spawnTotal, 'showCharacter' => true, 'moreUrl' => $listUrl('spawns')])
        </div>
    </div>
    </div>
</div>

<script>
    // "Space" in the hero opens the Space sub-tab.
    $('.rm-open-subtab').on('click', function (e) {
        e.preventDefault();
        $($(this).data('subtab')).tab('show');
    });
</script>
