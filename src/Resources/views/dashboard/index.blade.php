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
        <div class="row">
            <div class="col-12">
                <div class="card card-dark card-tabs">
                    <div class="card-header p-0 pt-1 d-flex flex-wrap align-items-center">
                        <ul class="nav nav-tabs flex-grow-1" id="rm-dashboard-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="rm-personal-tab" data-toggle="pill" href="#rm-personal" role="tab" aria-controls="rm-personal" aria-selected="true">
                                    <i class="fas fa-user"></i> {{ trans('ratting-manager::dashboard.my_ratting') }}
                                    <span class="badge personal-badge ml-2">{{ trans('ratting-manager::dashboard.personal_badge') }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="rm-corporation-tab" data-toggle="pill" href="#rm-corporation" role="tab" aria-controls="rm-corporation" aria-selected="false">
                                    <i class="fas fa-building"></i> {{ trans('ratting-manager::dashboard.corporation_ratting') }}
                                    <span class="badge corporation-badge ml-2">{{ trans('ratting-manager::dashboard.corporation_badge') }}</span>
                                </a>
                            </li>
                        </ul>
                        {{-- One period for both tabs. The character picker only narrows My Ratting. --}}
                        <div class="px-2 py-1 rm-shared-period">
                            @include('ratting-manager::partials.period-picker', [
                                'formId' => 'rm-period-form',
                                'characterOptions' => $characterOptions ?? [],
                                'pickedCharacter' => $pickedCharacter ?? null,
                                'tabInput' => request('tab') === 'corporation' ? 'corporation' : 'personal',
                            ])
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="rm-dashboard-content">
                            <div class="tab-pane fade show active" id="rm-personal" role="tabpanel" aria-labelledby="rm-personal-tab">
                                @include('ratting-manager::dashboard.partials.personal', ['showPicker' => false])
                            </div>
                            <div class="tab-pane fade" id="rm-corporation" role="tabpanel" aria-labelledby="rm-corporation-tab">
                                <div class="card card-dark">
                                    <div class="card-header">
                                        <h3 class="card-title"><i class="fas fa-building"></i> {{ trans('ratting-manager::dashboard.corporation_ratting') }}</h3>
                                        <div class="card-tools">
                                            {{-- The period comes from the shared picker above the tabs; this only picks the corporation,
                                                 and with one corporation tracked there is nothing to pick, so the form only carries the period. --}}
                                            @if(count($corporations) > 1)
                                                @include('ratting-manager::partials.period-picker', [
                                                    'formId' => 'rm-corp-filters',
                                                    'corporations' => $corporations,
                                                    'pickedCorporation' => null,
                                                    'characterOptions' => [],
                                                    'showPeriod' => false,
                                                ])
                                            @else
                                                <form id="rm-corp-filters" hidden><input type="hidden" name="period" value="{{ $period->key }}"></form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div id="rm-corp-loading" class="text-center py-5 text-muted">
                                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                                    <p class="mt-2">{{ trans('ratting-manager::dashboard.loading') }}</p>
                                </div>
                                <div id="rm-corp-content"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        @include('ratting-manager::dashboard.partials.personal')
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
