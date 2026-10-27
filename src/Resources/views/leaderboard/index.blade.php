@extends('ratting-manager::layouts.page')

@section('title', trans('ratting-manager::leaderboard.title'))
@section('page_header', trans('ratting-manager::leaderboard.title'))

@section('rm-tabs')
    @include('ratting-manager::partials.page-tabs', ['icon' => 'fa-trophy', 'label' => trans('ratting-manager::menu.leaderboard')])
@endsection

@php
    $portrait = fn (int $id, int $size = 64) => 'https://images.evetech.net/characters/' . $id . '/portrait?size=' . $size;
@endphp

@section('rm-body')
    @if($notConfigured)
        @include('ratting-manager::partials.not-configured')
    @elseif($isDirector)
        @php
            $ratted = ($incomeMode ?? 'received') === 'ratted';
            $ownIndex = null;
            foreach ($rows as $i => $r) {
                if ($ownMain !== null && $r['main_id'] === $ownMain) {
                    $ownIndex = $i;
                    break;
                }
            }
            $isk = fn ($v) => \RattingManager\Support\Format::isk($v);
            $value = fn (array $r) => $r['value'] ?? $r['income'];
        @endphp

        <div class="rm-page-head">
            @include('ratting-manager::partials.page-title', [
                'eyebrow' => trans('ratting-manager::leaderboard.eyebrow', ['brand' => $brand ?? trans('ratting-manager::common.plugin_name')]),
                'title' => $period->headline(),
                'subtitle' => trans('ratting-manager::leaderboard.subtitle'),
            ])
            <div class="rm-page-actions">
                @include('ratting-manager::partials.period-picker', ['corporations' => $corporations, 'pickedCorporation' => $pickedCorporation])
                <a class="btn rm-btn-outline btn-outline-secondary" href="{{ route('ratting-manager.leaderboard.csv', array_filter(['period' => $period->key, 'corporation' => $pickedCorporation])) }}">
                    {{ trans('ratting-manager::report.download_csv') }}
                </a>
            </div>
        </div>

        {{-- Totals --}}
        <div class="row">
            <div class="col-lg-3 col-sm-6">
                <div class="info-box"><div class="info-box-content">
                    <span class="info-box-text">{{ trans('ratting-manager::dashboard.' . ($ratted ? 'isk_ratted' : 'ratting_income')) }}</span>
                    <span class="info-box-number">@rmisk($ratted ? ($totals['gross'] ?? 0) : $totals['income'])</span>
                </div></div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="info-box rm-tile-tax"><div class="info-box-content">
                    <span class="info-box-text">{{ trans('ratting-manager::dashboard.ratting_tax') }}</span>
                    <span class="info-box-number">@rmisk($totals['corp_tax'])</span>
                    @if(($taxRate ?? null) !== null)
                        <small>{{ trans('ratting-manager::dashboard.tax_rate_now', ['rate' => \RattingManager\Support\Corporations::percent($taxRate)]) }}</small>
                    @endif
                </div></div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="info-box"><div class="info-box-content">
                    <span class="info-box-text">{{ trans('ratting-manager::dashboard.active_players') }}</span>
                    <span class="info-box-number">{{ number_format(count($rows)) }}</span>
                </div></div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="info-box"><div class="info-box-content">
                    <span class="info-box-text" title="{{ trans('ratting-manager::dashboard.median_hint') }}">{{ trans('ratting-manager::dashboard.median_isk_per_hour') }}</span>
                    <span class="info-box-number">@rmisk($median)</span>
                </div></div>
            </div>
        </div>

        @if(count($rows) > 0)
            {{-- The top three --}}
            <section class="rm-podium" aria-label="{{ trans('ratting-manager::leaderboard.top_three') }}">
                @foreach(array_slice($rows, 0, 3) as $i => $row)
                    <div class="rm-podium-card rm-medal-{{ $i + 1 }}">
                        <div class="rm-who">
                            <span class="rm-place">{{ $i + 1 }}</span>
                            <img class="rm-portrait rm-portrait-lg" src="{{ $portrait($row['main_id'], 128) }}" alt="" loading="lazy">
                            <div class="d-flex flex-column" style="min-width: 0">
                                <a class="rm-name" href="{{ route('ratting-manager.ratters.show', $row['main_id']) }}">{{ $row['name'] }}</a>
                                <span class="rm-sub">{{ trans_choice('ratting-manager::leaderboard.characters', count($row['characters']), ['count' => count($row['characters'])]) }}@if($row['left'] ?? false) · {{ trans('ratting-manager::common.left') }}@endif</span>
                            </div>
                        </div>
                        <div class="rm-money">
                            <strong>{{ $isk($value($row)) }}</strong>
                            <span>{{ trans('ratting-manager::leaderboard.tax_amount', ['amount' => $isk($row['corp_tax'])]) }}</span>
                        </div>
                        <div class="rm-meta">
                            <span>{{ trans('ratting-manager::leaderboard.hours_ratted', ['hours' => \RattingManager\Support\Format::hours($row['minutes'])]) }}</span>
                            <span>{{ trans('ratting-manager::leaderboard.per_hour', ['rate' => $isk($row['value_per_hour'] ?? $row['isk_per_hour'])]) }}</span>
                        </div>
                    </div>
                @endforeach
            </section>

            {{-- The viewer's own place, when they ratted --}}
            @if($ownIndex !== null)
                @php $own = $rows[$ownIndex]; @endphp
                <a class="rm-your-place" href="#rm-member-{{ $own['main_id'] }}" id="rm-your-place">
                    <span class="rm-tag">{{ mb_strtoupper(trans('ratting-manager::leaderboard.your_place')) }}</span>
                    <span class="rm-rank">#{{ $ownIndex + 1 }}</span>
                    <img class="rm-portrait rm-portrait-sm" src="{{ $portrait($own['main_id']) }}" alt="" loading="lazy">
                    <span class="rm-name">{{ $own['name'] }}</span>
                    <span class="rm-isk">{{ $isk($value($own)) }}</span>
                    <span class="rm-tax">{{ trans('ratting-manager::leaderboard.tax_amount', ['amount' => $isk($own['corp_tax'])]) }}</span>
                    <span class="rm-go">{{ trans('ratting-manager::leaderboard.show_my_row') }}</span>
                </a>
            @endif
        @endif

        {{-- Everyone, as cards or as the full table --}}
        <section aria-label="{{ trans('ratting-manager::leaderboard.members_heading') }}">
            <div class="rm-section-head">
                <div>
                    <div class="rm-eyebrow">{{ trans('ratting-manager::leaderboard.members_heading') }}</div>
                    <h2>{{ $period->headline() }}</h2>
                </div>
                <div class="rm-page-actions">
                    <span class="rm-results">{{ trans('ratting-manager::leaderboard.results') }} <strong id="rm-member-count">{{ count($rows) }}</strong></span>
                    <label for="rm-member-search" class="rm-sr-only">{{ trans('ratting-manager::leaderboard.search') }}</label>
                    <input id="rm-member-search" type="search" class="form-control rm-search" placeholder="{{ trans('ratting-manager::leaderboard.search') }}" autocomplete="off">
                    <div class="rm-segmented" role="group" aria-label="{{ trans('ratting-manager::leaderboard.view') }}">
                        <button type="button" data-rm-view="cards" aria-pressed="true">{{ trans('ratting-manager::leaderboard.view_cards') }}</button>
                        <button type="button" data-rm-view="table" aria-pressed="false">{{ trans('ratting-manager::leaderboard.view_table') }}</button>
                    </div>
                </div>
            </div>

            <div id="rm-view-cards" class="rm-member-grid">
                @foreach($rows as $i => $row)
                    <a href="{{ route('ratting-manager.ratters.show', $row['main_id']) }}" id="rm-member-{{ $row['main_id'] }}"
                       class="rm-member-card {{ $i === $ownIndex ? 'rm-you' : '' }}" data-rm-search="{{ mb_strtolower($row['name']) }}">
                        <img class="rm-portrait" src="{{ $portrait($row['main_id']) }}" alt="" loading="lazy">
                        <span class="rm-body">
                            <span class="rm-name">{{ $i + 1 }}. {{ $row['name'] }}@if($row['left'] ?? false)<span class="rm-left-tag" title="{{ trans('ratting-manager::common.left_title') }}">{{ mb_strtoupper(trans('ratting-manager::common.left')) }}</span>@endif @if($i === $ownIndex)<span class="rm-you-tag">{{ mb_strtoupper(trans('ratting-manager::leaderboard.you')) }}</span>@endif</span>
                            <span class="rm-money">{{ $isk($value($row)) }}</span>
                        </span>
                        <span class="rm-cut">{{ $isk($row['corp_tax']) }}</span>
                    </a>
                @endforeach
            </div>

            <div id="rm-view-table" class="card card-dark" hidden>
                <div class="card-body p-0">
                    @include('ratting-manager::leaderboard.partials.director-table', ['rows' => $rows, 'ownMain' => $ownMain])
                </div>
            </div>

            <p id="rm-member-empty" class="text-muted mt-3" hidden>{{ trans('ratting-manager::leaderboard.no_match') }}</p>
        </section>
    @else
        <div class="rm-page-head">
            @include('ratting-manager::partials.page-title', [
                'eyebrow' => trans('ratting-manager::leaderboard.eyebrow', ['brand' => $brand ?? trans('ratting-manager::common.plugin_name')]),
                'title' => $period->headline(),
                'subtitle' => trans('ratting-manager::leaderboard.subtitle_member'),
            ])
            <div class="rm-page-actions">
                @include('ratting-manager::partials.period-picker')
            </div>
        </div>

        @if($ownRank)
            @php $own = collect($rows)->firstWhere('main_id', $ownMain); @endphp
            <div class="rm-your-place">
                <span class="rm-tag">{{ mb_strtoupper(trans('ratting-manager::leaderboard.your_place')) }}</span>
                <span class="rm-rank">#{{ $ownRank }}</span>
                @if($own)
                    <img class="rm-portrait rm-portrait-sm" src="{{ $portrait($own['main_id']) }}" alt="" loading="lazy">
                    <span class="rm-name">{{ $own['name'] }}</span>
                    <span class="rm-isk">{{ trans('ratting-manager::leaderboard.hours_ratted', ['hours' => \RattingManager\Support\Format::hours($own['minutes'])]) }}</span>
                @endif
                <span class="rm-go">{{ trans('ratting-manager::leaderboard.your_rank', ['rank' => $ownRank, 'count' => $playerCount]) }}</span>
            </div>
        @else
            <p class="text-muted">{{ trans('ratting-manager::leaderboard.not_ratted') }}</p>
        @endif

        <div class="card card-dark">
            <div class="card-body p-0">
                <p class="text-muted small px-3 pt-3 mb-2">{{ trans('ratting-manager::leaderboard.members_note') }}</p>
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ trans('ratting-manager::leaderboard.rank') }}</th>
                            <th>{{ trans('ratting-manager::dashboard.player') }}</th>
                            <th class="text-right">{{ trans('ratting-manager::dashboard.hours') }}</th>
                            <th class="text-right">{{ trans('ratting-manager::dashboard.active_days') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr class="{{ $row['main_id'] === $ownMain ? 'rm-own-row' : '' }}">
                                <td>
                                    @if($row['rank'] <= 3)
                                        <span class="rm-rank-medal rm-medal-{{ $row['rank'] }}">{{ $row['rank'] }}</span>
                                    @else
                                        {{ $row['rank'] }}
                                    @endif
                                </td>
                                <td>
                                    <span class="d-inline-flex align-items-center" style="gap: 10px">
                                        <img class="rm-portrait rm-portrait-sm" src="{{ $portrait($row['main_id']) }}" alt="" loading="lazy">
                                        {{ $row['name'] }}
                                        @if($row['main_id'] === $ownMain)
                                            <span class="rm-you-tag">{{ mb_strtoupper(trans('ratting-manager::leaderboard.you')) }}</span>
                                        @endif
                                    </span>
                                </td>
                                <td class="text-right">{{ \RattingManager\Support\Format::hours($row['minutes']) }}</td>
                                <td class="text-right">{{ $row['active_days'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">{{ trans('ratting-manager::common.no_data') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

@section('rm-after')
    @include('ratting-manager::partials.character-lookup')
    @if(!$notConfigured && $isDirector)
    @push('javascript')
    <script>
    (function () {
        var KEY = 'rm-leaderboard-view';
        var $cards = $('#rm-view-cards'), $table = $('#rm-view-table');

        function show(view) {
            $cards.prop('hidden', view !== 'cards');
            $table.prop('hidden', view !== 'table');
            $('[data-rm-view]').each(function () {
                $(this).attr('aria-pressed', $(this).data('rm-view') === view ? 'true' : 'false');
            });
            try { localStorage.setItem(KEY, view); } catch (e) {}
        }

        // Cards and table rows are filtered together, so switching keeps the search.
        function filter() {
            var q = $.trim($('#rm-member-search').val()).toLowerCase();
            var shown = 0;
            $cards.children('[data-rm-search]').each(function () {
                var hit = !q || this.getAttribute('data-rm-search').indexOf(q) !== -1;
                this.hidden = !hit;
                if (hit) { shown++; }
            });
            $table.find('tbody tr[data-rm-search]').each(function () {
                this.hidden = !!q && this.getAttribute('data-rm-search').indexOf(q) === -1;
            });
            $('#rm-member-count').text(shown);
            $('#rm-member-empty').prop('hidden', shown > 0);
        }

        $('[data-rm-view]').on('click', function () { show($(this).data('rm-view')); });
        $('#rm-member-search').on('input', filter);

        // "Show my row" clears the search and lands on the viewer's card or row.
        $('#rm-your-place').on('click', function () {
            $('#rm-member-search').val('');
            filter();
            if (!$cards.prop('hidden')) { return; }
            var $row = $table.find('tr.rm-own-row').not('.rm-pinned-row').first();
            if ($row.length) {
                $row[0].scrollIntoView({ block: 'center' });
                return false;
            }
        });

        var saved = null;
        try { saved = localStorage.getItem(KEY); } catch (e) {}
        show(saved === 'table' ? 'table' : 'cards');
    })();
    </script>
    @endpush
    @endif
@endsection
