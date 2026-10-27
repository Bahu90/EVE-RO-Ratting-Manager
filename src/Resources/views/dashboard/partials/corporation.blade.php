{{-- The Corporation tab, rendered on request and dropped into the dashboard. Directors only. --}}

<div class="row">
    <div class="col-12">
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie"></i> {{ $period->label() }}</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <div class="info-box bg-gradient-success">
                            <span class="info-box-icon"><i class="fas fa-coins"></i></span>
                            <div class="info-box-content">
                                @if(($incomeMode ?? 'received') === 'ratted')
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
                    <div class="col-lg-3 col-md-6">
                        <div class="info-box bg-gradient-danger">
                            <span class="info-box-icon"><i class="fas fa-landmark"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">{{ trans('ratting-manager::dashboard.corp_tax') }}</span>
                                <span class="info-box-number">@rmisk($totals['corp_tax'])</span>
                                @if(($totals['alliance_rate'] ?? 0) > 0)
                                    <small title="{{ trans('ratting-manager::dashboard.alliance_tax_title') }}">{{ trans('ratting-manager::dashboard.alliance_tax_line', ['amount' => \RattingManager\Support\Format::isk($totals['alliance_tax']), 'rate' => rtrim(rtrim(number_format($totals['alliance_rate'] * 100, 2), '0'), '.')]) }}</small>
                                @else
                                    <small>{{ trans('ratting-manager::dashboard.corp_tax_hint') }}</small>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="info-box bg-gradient-primary">
                            <span class="info-box-icon"><i class="fas fa-users"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">{{ trans('ratting-manager::dashboard.active_players') }}</span>
                                <span class="info-box-number">{{ number_format($totals['players']) }}</span>
                                <small>{{ trans('ratting-manager::dashboard.members_sub', ['count' => number_format($totals['characters'])]) }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="info-box bg-gradient-info">
                            <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">{{ trans('ratting-manager::dashboard.hours_ratted') }}</span>
                                <span class="info-box-number">{{ \RattingManager\Support\Format::hours((int) $totals['minutes']) }}</span>
                                <small title="{{ trans('ratting-manager::dashboard.median_hint') }}">{{ trans('ratting-manager::dashboard.hours_corp_sub', ['rate' => \RattingManager\Support\Format::isk($median)]) }}</small>
                            </div>
                        </div>
                    </div>
                </div>
                @isset($space)
                    <p class="text-muted mb-0 rm-space-counts">
                        <i class="fas fa-globe"></i>
                        {{ trans('ratting-manager::dashboard.space_counts', ['systems' => number_format($space['systems']), 'constellations' => number_format($space['constellations']), 'regions' => number_format($space['regions'])]) }}
                    </p>
                @endisset
            </div>
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
    <div class="col-lg-6">
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line"></i> {{ trans('ratting-manager::dashboard.daily_income') }}</h3>
            </div>
            <div class="card-body"><div class="rm-chart"><canvas id="rm-corp-daily"></canvas></div><p class="rm-chart-note">{{ trans('ratting-manager::dashboard.daily_income_note') }}</p></div>
        </div>
    </div>
    <div class="col-lg-6">
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

<div class="row">
    <div class="col-12">
        @include('ratting-manager::partials.systems-table', ['rows' => $insights['systems'], 'corporation' => true, 'limit' => \RattingManager\Http\Controllers\ListController::DASHBOARD_ROWS, 'moreUrl' => $listUrl('systems')])
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        @include('ratting-manager::partials.npc-table', ['npcs' => $insights['npcs'], 'rats' => $totals['rats']])
    </div>
    <div class="col-lg-6">
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
