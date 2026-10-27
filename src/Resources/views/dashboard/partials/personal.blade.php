{{--
    My Ratting: one player's figures. Used on the dashboard for the signed-in
    member, and on the ratter page for any player a director opens.
--}}
@php
    $workedOut = $totals['estimated'] ?? 0;
    // ISK ratted (the corporation's count) or income received, as set under Settings.
    $ratted = ($incomeMode ?? 'received') === 'ratted';
    $perHour = $ratted
        ? ($totals['gross_per_hour'] ?? ($totals['minutes'] > 0 ? ($totals['gross'] ?? 0) / ($totals['minutes'] / 60) : 0))
        : $totals['isk_per_hour'];
    // The corporation's cut as a share of everything the ratting paid out.
    $paidOut = $totals['income'] + $totals['corp_tax'];
    $taxShare = $totals['corp_tax'] > 0 && $paidOut > 0 ? number_format($totals['corp_tax'] / $paidOut * 100, 1) : null;
@endphp

<div class="row">
    <div class="col-12">
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-crosshairs"></i>
                    {{ $heading ?? trans('ratting-manager::dashboard.my_ratting') }} - {{ $period->label() }}
                </h3>
                <div class="card-tools">
                    @include('ratting-manager::partials.period-picker', [
                        'characterOptions' => $characterOptions ?? [],
                        'pickedCharacter' => $pickedCharacter ?? null,
                    ])
                </div>
            </div>
            <div class="card-body">
                {{-- Four boxes: what was made, what the corporation took, how long, and where that ranks. --}}
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <div class="info-box bg-gradient-success">
                            <span class="info-box-icon"><i class="fas fa-coins"></i></span>
                            <div class="info-box-content">
                                @if($ratted)
                                    <span class="info-box-text" title="{{ trans('ratting-manager::dashboard.isk_ratted_title') }}">{{ trans('ratting-manager::dashboard.isk_ratted') }}</span>
                                    <span class="info-box-number">@rmisk($totals['gross'] ?? 0)</span>
                                    <small>{{ trans('ratting-manager::dashboard.received_by_pilots', ['amount' => \RattingManager\Support\Format::isk($totals['income'])]) }}</small>
                                @else
                                    <span class="info-box-text">{{ trans('ratting-manager::dashboard.ratting_income') }}</span>
                                    <span class="info-box-number">@rmisk($totals['income'])</span>
                                    @if($workedOut > 0)
                                        <small class="rm-estimated" title="{{ trans('ratting-manager::common.worked_out_title') }}">{{ trans('ratting-manager::dashboard.worked_out_share', ['amount' => \RattingManager\Support\Format::isk($workedOut)]) }}</small>
                                    @endif
                                @endif
                                <small>{{ trans('ratting-manager::dashboard.bounty_ess_split', ['bounty' => \RattingManager\Support\Format::isk($totals['bounty']), 'ess' => \RattingManager\Support\Format::isk($totals['ess'])]) }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="info-box bg-gradient-danger">
                            <span class="info-box-icon"><i class="fas fa-landmark"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">{{ trans('ratting-manager::dashboard.ratting_tax') }}</span>
                                <span class="info-box-number">@rmisk($totals['corp_tax'])</span>
                                @if($taxShare !== null)
                                    <small>{{ trans('ratting-manager::dashboard.tax_paid_hint', ['rate' => $taxShare]) }}</small>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="info-box bg-gradient-info">
                            <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">{{ trans('ratting-manager::dashboard.hours_ratted') }}</span>
                                <span class="info-box-number">{{ \RattingManager\Support\Format::hours((int) $totals['minutes']) }}</span>
                                <small>{{ trans('ratting-manager::dashboard.hours_sub', ['rate' => \RattingManager\Support\Format::isk($perHour), 'days' => $totals['active_days']]) }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="info-box bg-gradient-primary">
                            <span class="info-box-icon"><i class="fas fa-trophy"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">{{ trans('ratting-manager::dashboard.corp_rank') }}</span>
                                @if(isset($ownRank) && $ownRank)
                                    <span class="info-box-number">#{{ $ownRank }}</span>
                                    <small>{{ trans('ratting-manager::dashboard.corp_rank_of', ['count' => $rankedPlayers]) }}</small>
                                @else
                                    <span class="info-box-number">-</span>
                                    <small>{{ trans('ratting-manager::dashboard.not_ranked') }}</small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        @include('ratting-manager::partials.insights', ['insights' => $insights, 'corporation' => false])
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar"></i> {{ trans('ratting-manager::dashboard.income_12_months') }}</h3>
            </div>
            <div class="card-body"><div class="rm-chart"><canvas id="rm-chart-monthly"></canvas></div><p class="rm-chart-note">{{ trans('ratting-manager::dashboard.income_12_months_note') }}</p></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-medal"></i> {{ trans('ratting-manager::dashboard.records') }}</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        @foreach(['best_tick', 'best_session', 'best_day', 'best_month'] as $record)
                            <tr>
                                <td>{{ trans('ratting-manager::dashboard.' . $record) }}</td>
                                @if($records[$record])
                                    <td class="text-right">
                                        <strong>@rmisk($records[$record]['value'])</strong><br>
                                        <small class="text-muted">{{ $records[$record]['when'] }}</small>
                                    </td>
                                @else
                                    <td class="text-right text-muted">-</td>
                                @endif
                            </tr>
                        @endforeach
                        <tr>
                            <td>{{ trans('ratting-manager::dashboard.longest_session') }}</td>
                            @if($records['longest_session'])
                                <td class="text-right">
                                    <strong>{{ \RattingManager\Support\Format::hours($records['longest_session']['minutes']) }}</strong><br>
                                    <small class="text-muted">{{ $records['longest_session']['when'] }}</small>
                                </td>
                            @else
                                <td class="text-right text-muted">-</td>
                            @endif
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line"></i> {{ trans('ratting-manager::dashboard.daily_income') }}</h3>
            </div>
            <div class="card-body"><div class="rm-chart"><canvas id="rm-chart-daily"></canvas></div><p class="rm-chart-note">{{ trans('ratting-manager::dashboard.daily_income_note') }}</p></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-th"></i> {{ trans('ratting-manager::dashboard.when_i_rat') }}</h3>
            </div>
            <div class="card-body">
                @include('ratting-manager::partials.heatmap', ['heatmap' => $heatmap, 'perPlayer' => false, 'title' => trans('ratting-manager::dashboard.when_i_rat')])
                <p class="rm-chart-note">{{ trans('ratting-manager::dashboard.when_i_rat_note') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        @include('ratting-manager::partials.systems-table', ['rows' => $insights['systems'], 'corporation' => false, 'limit' => \RattingManager\Http\Controllers\ListController::DASHBOARD_ROWS, 'moreUrl' => isset($listUrl) ? $listUrl('systems') : null])
    </div>
</div>

{{-- What was killed: the NPC table alone (it holds the faction, size and count of each type), beside each session's ISK per hour. --}}
<div class="row">
    <div class="col-lg-6">
        @include('ratting-manager::partials.npc-table', ['npcs' => $insights['npcs'], 'rats' => $totals['rats']])
    </div>
    <div class="col-lg-6">
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-stopwatch"></i> {{ trans('ratting-manager::dashboard.session_isk_per_hour') }}</h3>
            </div>
            <div class="card-body"><div class="rm-chart"><canvas id="rm-chart-sessions"></canvas></div><p class="rm-chart-note">{{ trans('ratting-manager::dashboard.session_isk_per_hour_note') }}</p></div>
        </div>
    </div>
</div>

@if(count($personalCharts['characters']['labels']) > 1)
    <div class="row">
        <div class="col-12">
            <div class="card card-dark">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-users"></i> {{ trans('ratting-manager::dashboard.by_character') }}</h3>
                </div>
                <div class="card-body"><div class="rm-chart"><canvas id="rm-chart-characters"></canvas></div><p class="rm-chart-note">{{ trans('ratting-manager::dashboard.by_character_note') }}</p></div>
            </div>
        </div>
    </div>
@endif

<div class="row">
    <div class="col-12">
        @include('ratting-manager::dashboard.partials.month-by-character')
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        @include('ratting-manager::dashboard.partials.sessions-table', [
            'rows' => $sessions['list'],
            'total' => $sessions['total'],
            'moreUrl' => isset($listUrl) ? $listUrl('sessions') : null,
        ])
    </div>
    <div class="col-lg-4">
        @include('ratting-manager::dashboard.partials.spawns', ['spawns' => $spawns, 'total' => $spawnTotal, 'showCharacter' => true, 'moreUrl' => isset($listUrl) ? $listUrl('spawns') : null])
    </div>
</div>
