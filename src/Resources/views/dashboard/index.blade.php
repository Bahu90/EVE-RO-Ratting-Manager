@extends('web::layouts.grids.12')

@section('title', trans('ratting-manager::dashboard.title'))
@section('page_header', trans('ratting-manager::dashboard.title'))

@push('head')
    @include('ratting-manager::partials.head')
@endpush

@section('full')
<div class="ratting-manager-dashboard">
    @if($notConfigured)
        @include('ratting-manager::partials.not-configured')
    @elseif($isDirector)
        <div class="rm-page-head">
            @include('ratting-manager::partials.page-title', [
                'eyebrow' => trans('ratting-manager::dashboard.eyebrow'),
                'title' => $period->headline(),
                'subtitle' => trans('ratting-manager::dashboard.subtitle'),
            ])
            <div class="rm-page-actions">
                {{-- Mine / Corporation. A .nav so Bootstrap switches the panes. --}}
                <div class="nav rm-segmented" id="rm-dashboard-tabs" role="tablist">
                    <a class="nav-link active" id="rm-personal-tab" data-toggle="pill" href="#rm-personal" role="tab" aria-controls="rm-personal" aria-selected="true">{{ trans('ratting-manager::dashboard.view_mine') }}</a>
                    <a class="nav-link" id="rm-corporation-tab" data-toggle="pill" href="#rm-corporation" role="tab" aria-controls="rm-corporation" aria-selected="false">{{ trans('ratting-manager::dashboard.view_corporation') }}</a>
                </div>
                {{-- One period for both views. The character picker only narrows Mine. --}}
                <div class="rm-shared-period">
                    @include('ratting-manager::partials.period-picker', [
                        'formId' => 'rm-period-form',
                        'characterOptions' => $characterOptions ?? [],
                        'pickedCharacter' => $pickedCharacter ?? null,
                        'tabInput' => request('tab') === 'corporation' ? 'corporation' : 'personal',
                    ])
                </div>
            </div>
        </div>

        <div class="tab-content" id="rm-dashboard-content">
            <div class="tab-pane fade show active" id="rm-personal" role="tabpanel" aria-labelledby="rm-personal-tab">
                @include('ratting-manager::dashboard.partials.personal', ['showPicker' => false, 'headed' => true])
            </div>
            <div class="tab-pane fade" id="rm-corporation" role="tabpanel" aria-labelledby="rm-corporation-tab">
                {{-- The period comes from the shared picker; this only picks the corporation,
                     and with one corporation tracked the form only carries the period. --}}
                @if(count($corporations) > 1)
                    <div class="d-flex justify-content-end mb-3">
                        @include('ratting-manager::partials.period-picker', [
                            'formId' => 'rm-corp-filters',
                            'corporations' => $corporations,
                            'pickedCorporation' => null,
                            'characterOptions' => [],
                            'showPeriod' => false,
                        ])
                    </div>
                @else
                    <form id="rm-corp-filters" hidden><input type="hidden" name="period" value="{{ $period->key }}"></form>
                @endif
                <div id="rm-corp-loading" class="text-center py-5 text-muted">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">{{ trans('ratting-manager::dashboard.loading') }}</p>
                </div>
                <div id="rm-corp-content"></div>
            </div>
        </div>
    @else
        <div class="rm-page-head">
            @include('ratting-manager::partials.page-title', [
                'eyebrow' => trans('ratting-manager::dashboard.eyebrow'),
                'title' => $period->headline(),
                'subtitle' => trans('ratting-manager::dashboard.subtitle_member'),
            ])
            <div class="rm-page-actions">
                @include('ratting-manager::partials.period-picker', [
                    'characterOptions' => $characterOptions ?? [],
                    'pickedCharacter' => $pickedCharacter ?? null,
                ])
            </div>
        </div>
        @include('ratting-manager::dashboard.partials.personal', ['headed' => true])
    @endif
</div>
    @include('ratting-manager::partials.character-lookup')
@endsection

@push('javascript')
<script src="{{ \RattingManager\Support\Asset::url('js/vendor/chart.min.js') }}"></script>
<script src="{{ \RattingManager\Support\Asset::url('js/eve-time.js') }}"></script>
<script src="{{ \RattingManager\Support\Asset::url('js/ratting-charts.js') }}"></script>
@if(!$notConfigured)
@php
    $chartText = trans('ratting-manager::dashboard') + ['insights' => trans('ratting-manager::insights')];
@endphp
<script>
(function () {
    var t = @json($chartText);
    RattingCharts.personal(@json($personalCharts), t);

    @if($isDirector)
    var loaded = false;
    var tabUrl = @json(route('ratting-manager.dashboard.tab.corporation'));

    function loadCorporation(query) {
        $('#rm-corp-content').empty();
        $('#rm-corp-loading').show();
        $.getJSON(tabUrl + '?' + query)
            .done(function (resp) {
                $('#rm-corp-content').html(resp.html);
                $('#rm-corp-loading').hide();
                RattingCharts.corporation(resp.charts, t);
                if (window.rmCharacterLookup) { window.rmCharacterLookup.watch(resp.pending || []); }
                if (window.EveTime) { window.EveTime.convert(document.getElementById('rm-corp-content')); }
            })
            .fail(function () {
                $('#rm-corp-loading').html(
                    '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ' +
                    $('<div>').text(t.load_failed).html() + ' <a href="#" onclick="location.reload()">' +
                    $('<div>').text(t.reload).html() + '</a></div>'
                );
            });
    }

    $('#rm-corporation-tab').on('shown.bs.tab', function () {
        if (!loaded) {
            loaded = true;
            loadCorporation($('#rm-corp-filters').serialize());
        }
    });

    // The shared period picker remembers which tab was open, so changing
    // the period reloads the page on the same tab.
    $('#rm-dashboard-tabs a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
        $('#rm-tab-input').val(e.target.id === 'rm-corporation-tab' ? 'corporation' : 'personal');
    });

    // Coming back from one of the tab's full lists, or after changing the
    // period on the Corporation tab, opens the tab again.
    if (location.hash === '#rm-corporation' || $('#rm-tab-input').val() === 'corporation') {
        $('#rm-corporation-tab').tab('show');
    }

    // The tab's own filters reload only the tab, so My Ratting keeps its period.
    $('#rm-corp-filters').on('submit', function (e) {
        e.preventDefault();
        loadCorporation($(this).serialize());
    });
    @endif
})();
</script>
@endif
@endpush
