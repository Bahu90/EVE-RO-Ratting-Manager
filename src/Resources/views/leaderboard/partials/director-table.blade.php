{{--
    Every column, for directors: the Corporation tab shows the top of it, the
    Leaderboard page all of it.

    The viewer's own row is highlighted, and when it is further down than the
    pinned limit it is also shown first, marked "You", so nobody has to
    scroll to find themselves. On narrow screens only rank, player, the
    money columns and hours show; the rest come back from the lg breakpoint.
--}}
@php
    $shown = isset($limit) ? array_slice($rows, 0, $limit) : $rows;
    // ISK ratted (the corporation's count) or income received, as set under Settings.
    $ratted = ($incomeMode ?? 'received') === 'ratted';

    $ownIndex = null;
    foreach ($rows as $i => $r) {
        if (($ownMain ?? null) !== null && $r['main_id'] === $ownMain) {
            $ownIndex = $i;
            break;
        }
    }
    $pinAfter = 10;
    $lines = [];
    if ($ownIndex !== null && $ownIndex >= $pinAfter) {
        $lines[] = [$ownIndex, $rows[$ownIndex], true];
    }
    foreach ($shown as $i => $r) {
        $lines[] = [$i, $r, false];
    }

    // Shown from lg up: secondary columns that crowd a phone screen.
    $wide = 'd-none d-lg-table-cell';
@endphp
<div class="table-responsive">
    <table class="table table-sm table-hover mb-0 rm-leaderboard">
        <thead>
            <tr>
                <th>{{ trans('ratting-manager::leaderboard.rank') }}</th>
                <th>{{ trans('ratting-manager::dashboard.player') }}</th>
                <th class="text-right" title="{{ trans('ratting-manager::dashboard.' . ($ratted ? 'isk_ratted_title' : 'income_title')) }}">{{ trans('ratting-manager::dashboard.' . ($ratted ? 'isk_ratted' : 'income')) }}</th>
                <th class="text-right" title="{{ trans('ratting-manager::dashboard.ratting_tax_title') }}">{{ trans('ratting-manager::dashboard.ratting_tax') }}</th>
                <th class="text-right">{{ trans('ratting-manager::dashboard.hours') }}</th>
                <th class="text-right d-none d-md-table-cell">{{ trans('ratting-manager::dashboard.isk_per_hour') }}</th>
                <th class="text-right {{ $wide }}" title="{{ trans('ratting-manager::dashboard.vs_median_title') }}">{{ trans('ratting-manager::dashboard.vs_median') }}</th>
                <th class="text-right {{ $wide }}">{{ trans('ratting-manager::dashboard.rats') }}</th>
                <th class="text-right {{ $wide }}">{{ trans('ratting-manager::dashboard.active_days') }}</th>
                <th class="text-right {{ $wide }}" title="{{ trans('ratting-manager::dashboard.change_title') }}">{{ trans('ratting-manager::dashboard.change') }}</th>
                <th class="{{ $wide }}" title="{{ trans('ratting-manager::common.coverage_header_title') }}">{{ trans('ratting-manager::dashboard.coverage') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lines as [$i, $row, $pinned])
                <tr class="{{ $i === $ownIndex ? 'rm-own-row' : '' }} {{ $pinned ? 'rm-pinned-row' : '' }}" data-rm-search="{{ mb_strtolower($row['name']) }}">
                    <td>
                        @if($i < 3)
                            <span class="rm-rank-medal rm-medal-{{ $i + 1 }}">{{ $i + 1 }}</span>
                        @else
                            {{ $i + 1 }}
                        @endif
                    </td>
                    <td>
                        <img class="rm-portrait rm-portrait-sm mr-2" src="https://images.evetech.net/characters/{{ $row['main_id'] }}/portrait?size=64" alt="" loading="lazy">
                        <a href="{{ route('ratting-manager.ratters.show', $row['main_id']) }}">{{ $row['name'] }}</a>
                        @if(count($row['characters']) > 1)
                            <small class="text-muted">(+{{ count($row['characters']) - 1 }})</small>
                        @endif
                        @if($row['left'] ?? false)
                            <span class="rm-left-tag" title="{{ trans('ratting-manager::common.left_title') }}">{{ mb_strtoupper(trans('ratting-manager::common.left')) }}</span>
                        @endif
                        @if($i === $ownIndex)
                            <span class="rm-you-tag">{{ mb_strtoupper(trans('ratting-manager::leaderboard.you')) }}</span>
                        @endif
                    </td>
                    <td class="text-right rm-isk">
                        @if(!$ratted && $row['estimated'] > 0)
                            <span class="rm-estimated" title="{{ trans('ratting-manager::common.worked_out_title') }}">@rmisk($row['value'] ?? $row['income'])</span>
                        @else
                            @rmisk($row['value'] ?? $row['income'])
                        @endif
                    </td>
                    <td class="text-right rm-tax">@rmisk($row['corp_tax'])</td>
                    <td class="text-right">{{ \RattingManager\Support\Format::hours($row['minutes']) }}</td>
                    <td class="text-right d-none d-md-table-cell">@rmisk($row['value_per_hour'] ?? $row['isk_per_hour'])</td>
                    <td class="text-right {{ $wide }}">
                        @if($row['efficiency'] === null)
                            <span class="text-muted">-</span>
                        @elseif($row['efficiency'] >= 100)
                            <span class="rm-efficiency-up"><i class="fas fa-arrow-up"></i> {{ $row['efficiency'] }}%</span>
                        @else
                            <span class="rm-efficiency-down"><i class="fas fa-arrow-down"></i> {{ $row['efficiency'] }}%</span>
                        @endif
                    </td>
                    <td class="text-right {{ $wide }}">{{ number_format($row['rats']) }}</td>
                    <td class="text-right {{ $wide }}">{{ $row['active_days'] }}</td>
                    <td class="text-right {{ $wide }}">
                        @if($row['change'] === null)
                            <span class="text-muted">-</span>
                        @elseif($row['change'] >= 0)
                            <span class="rm-efficiency-up"><i class="fas fa-caret-up"></i> {{ $row['change'] }}%</span>
                        @else
                            <span class="rm-efficiency-down"><i class="fas fa-caret-down"></i> {{ abs($row['change']) }}%</span>
                        @endif
                    </td>
                    <td class="{{ $wide }}">@include('ratting-manager::partials.coverage-badge', ['coverage' => $row['coverage']])</td>
                </tr>
            @empty
                <tr><td colspan="11" class="text-center text-muted">{{ trans('ratting-manager::common.no_data') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
