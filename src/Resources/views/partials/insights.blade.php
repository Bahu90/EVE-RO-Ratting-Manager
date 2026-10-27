{{--
    The insight tiles: where, when and how well. $insights comes from
    Insights::for(); $corporation swaps the two personal tiles (sessions,
    income per active day) for two about the corporation's players.

    Six tiles each way. Busiest and best weekday are left to the heatmap,
    which shows days and hours together; rats per hour, favourite faction
    and ESS share were trivia beside the figures above them.
--}}
@php
    $h = $insights['highlights'];
    $corporation = $corporation ?? false;
    $fmt = \RattingManager\Support\Format::class;
@endphp

<div class="card card-dark">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-lightbulb"></i> {{ trans('ratting-manager::insights.title') }}</h3>
        <div class="card-tools"><small class="text-muted">{{ trans('ratting-manager::insights.title_hint') }}</small></div>
    </div>
    <div class="card-body">
        <div class="rm-insights">
            <div class="rm-insight {{ $h['favourite_system'] ? '' : 'rm-insight-empty' }}">
                <div class="rm-insight-label"><i class="fas fa-map-marker-alt"></i> {{ trans('ratting-manager::insights.favourite_system') }}</div>
                @if($h['favourite_system'])
                    <div class="rm-insight-value" title="{{ $h['favourite_system']['name'] }}">{{ $h['favourite_system']['name'] }}</div>
                    <div class="rm-insight-sub">{{ $fmt::hours($h['favourite_system']['minutes']) }}, {{ trans('ratting-manager::insights.of_hours', ['share' => $h['favourite_system']['hours_share']]) }}</div>
                @else
                    <div class="rm-insight-value">{{ trans('ratting-manager::insights.nothing') }}</div>
                @endif
            </div>

            <div class="rm-insight {{ $h['best_system'] ? '' : 'rm-insight-empty' }}">
                <div class="rm-insight-label"><i class="fas fa-bolt"></i> {{ trans('ratting-manager::insights.best_system') }}</div>
                @if($h['best_system'])
                    <div class="rm-insight-value" title="{{ $h['best_system']['name'] }}">{{ $h['best_system']['name'] }}</div>
                    <div class="rm-insight-sub">@rmisk($h['best_system']['isk_per_hour']) {{ trans('ratting-manager::insights.per_hour') }}</div>
                @else
                    <div class="rm-insight-value" title="{{ trans('ratting-manager::insights.min_hour_note') }}">{{ trans('ratting-manager::insights.nothing') }}</div>
                @endif
            </div>

            <div class="rm-insight {{ $h['busiest_window'] ? '' : 'rm-insight-empty' }}">
                <div class="rm-insight-label"><i class="fas fa-clock"></i> {{ trans('ratting-manager::insights.busiest_window') }}</div>
                @if($h['busiest_window'])
                    <div class="rm-insight-value">{{ $h['busiest_window']['label'] }} <small class="text-muted">{{ trans('ratting-manager::insights.eve') }}</small></div>
                    <div class="rm-insight-sub">{{ trans('ratting-manager::insights.of_hours', ['share' => $insights['totals']['minutes'] > 0 ? round($h['busiest_window']['minutes'] / $insights['totals']['minutes'] * 100) : 0]) }}</div>
                @else
                    <div class="rm-insight-value">{{ trans('ratting-manager::insights.nothing') }}</div>
                @endif
            </div>

            <div class="rm-insight {{ $h['best_window'] ? '' : 'rm-insight-empty' }}">
                <div class="rm-insight-label"><i class="fas fa-hourglass-half"></i> {{ trans('ratting-manager::insights.best_window') }}</div>
                @if($h['best_window'])
                    <div class="rm-insight-value">{{ $h['best_window']['label'] }} <small class="text-muted">{{ trans('ratting-manager::insights.eve') }}</small></div>
                    <div class="rm-insight-sub">@rmisk($h['best_window']['isk_per_hour']) {{ trans('ratting-manager::insights.per_hour') }}</div>
                @else
                    <div class="rm-insight-value" title="{{ trans('ratting-manager::insights.min_hour_note') }}">{{ trans('ratting-manager::insights.nothing') }}</div>
                @endif
            </div>

            @if($corporation)
                <div class="rm-insight {{ $h['peak_concurrent'] ? '' : 'rm-insight-empty' }}">
                    <div class="rm-insight-label"><i class="fas fa-users"></i> {{ trans('ratting-manager::insights.peak_concurrent') }}</div>
                    @if($h['peak_concurrent'])
                        <div class="rm-insight-value">{{ $h['peak_concurrent']['players'] }}</div>
                        <div class="rm-insight-sub">{{ trans('ratting-manager::insights.peak_concurrent_hint', ['when' => $h['peak_concurrent']['when']]) }}</div>
                    @else
                        <div class="rm-insight-value">{{ trans('ratting-manager::insights.nothing') }}</div>
                    @endif
                </div>

                <div class="rm-insight">
                    <div class="rm-insight-label"><i class="fas fa-user-tag"></i> {{ trans('ratting-manager::insights.income_per_player') }}</div>
                    <div class="rm-insight-value">@rmisk($h['income_per_player'])</div>
                    <div class="rm-insight-sub">{{ trans('ratting-manager::insights.players_per_day_sub', ['count' => number_format($h['players_per_day'], 1)]) }}</div>
                </div>
            @else
                <div class="rm-insight {{ $h['sessions']['count'] ? '' : 'rm-insight-empty' }}">
                    <div class="rm-insight-label"><i class="fas fa-stopwatch"></i> {{ trans('ratting-manager::insights.sessions') }}</div>
                    <div class="rm-insight-value">{{ number_format($h['sessions']['count']) }}</div>
                    @if($h['sessions']['count'])
                        <div class="rm-insight-sub">{{ trans('ratting-manager::insights.typical_session', ['time' => $fmt::hours($h['sessions']['median_minutes']), 'longest' => $fmt::hours($h['sessions']['longest_minutes'])]) }}</div>
                    @endif
                </div>

                <div class="rm-insight">
                    <div class="rm-insight-label"><i class="fas fa-coins"></i> {{ trans('ratting-manager::insights.income_per_day') }}</div>
                    <div class="rm-insight-value">@rmisk($h['income_per_active_day'])</div>
                    <div class="rm-insight-sub">{{ trans('ratting-manager::insights.days', ['count' => $h['active_days']]) }}, {{ trans('ratting-manager::insights.longest_streak_sub', ['count' => $h['longest_streak']]) }}</div>
                </div>
            @endif
        </div>
    </div>
</div>
