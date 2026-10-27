@extends('ratting-manager::layouts.page')

@section('title', trans('ratting-manager::ratter.lookup_title'))
@section('page_header', trans('ratting-manager::ratter.lookup_title'))

@push('head')
    <link rel="stylesheet" href="{{ \RattingManager\Support\Asset::url('css/vendor/dataTables.bootstrap4.min.css') }}">
@endpush

@section('rm-tabs')
    @include('ratting-manager::partials.page-tabs', ['icon' => 'fa-search', 'label' => trans('ratting-manager::menu.ratter_lookup')])
@endsection

@section('rm-body')
    @if($notConfigured)
        @include('ratting-manager::partials.not-configured', ['isAdmin' => \RattingManager\Support\Access::isAdmin()])
    @else
        @php $ratted = ($incomeMode ?? 'received') === 'ratted'; @endphp

        <div class="rm-page-head">
            @include('ratting-manager::partials.page-title', [
                'eyebrow' => trans('ratting-manager::ratter.eyebrow'),
                'title' => $period->headline(),
                'subtitle' => trans('ratting-manager::ratter.subtitle'),
            ])
            <div class="rm-page-actions">
                @include('ratting-manager::partials.period-picker')
            </div>
        </div>

        <div class="row">
            <div class="col-lg-3 col-sm-6">
                <div class="info-box"><div class="info-box-content">
                    <span class="info-box-text">{{ trans('ratting-manager::ratter.players') }}</span>
                    <span class="info-box-number">{{ number_format($counts['players']) }}</span>
                </div></div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="info-box"><div class="info-box-content">
                    <span class="info-box-text">{{ trans('ratting-manager::ratter.characters') }}</span>
                    <span class="info-box-number">{{ number_format($counts['characters']) }}</span>
                </div></div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="info-box rm-tile-tax"><div class="info-box-content">
                    <span class="info-box-text">{{ trans('ratting-manager::ratter.wallets_linked') }}</span>
                    <span class="info-box-number">{{ number_format($counts['linked']) }}</span>
                </div></div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="info-box rm-tile-warn"><div class="info-box-content">
                    <span class="info-box-text">{{ trans('ratting-manager::ratter.wallets_not_linked') }}</span>
                    <span class="info-box-number">{{ number_format($counts['not_linked']) }}</span>
                </div></div>
            </div>
        </div>

        <div class="rm-section-head">
            <div class="rm-segmented" role="group" aria-label="{{ trans('ratting-manager::ratter.filter') }}" id="rm-wallet-filter">
                <button type="button" data-rm-wallet="" aria-pressed="true">{{ trans('ratting-manager::ratter.filter_all') }}</button>
                <button type="button" data-rm-wallet="1" aria-pressed="false">{{ trans('ratting-manager::ratter.filter_linked') }}</button>
                <button type="button" data-rm-wallet="0" aria-pressed="false">{{ trans('ratting-manager::ratter.filter_not') }}</button>
            </div>
        </div>

        <div class="card card-dark">
            <div class="card-body">
                <table class="table table-sm table-hover" id="rm-ratters">
                    <thead>
                        <tr>
                            <th>{{ trans('ratting-manager::dashboard.player') }}</th>
                            <th class="text-right" title="{{ trans('ratting-manager::dashboard.' . ($ratted ? 'isk_ratted_title' : 'income_title')) }}">{{ trans('ratting-manager::ratter.' . ($ratted ? 'ratted' : 'income')) }}</th>
                            <th class="text-right" title="{{ trans('ratting-manager::dashboard.ratting_tax_title') }}">{{ trans('ratting-manager::dashboard.ratting_tax') }}</th>
                            <th class="text-right">{{ trans('ratting-manager::dashboard.hours') }}</th>
                            <th class="text-right d-none d-md-table-cell">{{ trans('ratting-manager::dashboard.isk_per_hour') }}</th>
                            <th class="text-right d-none d-lg-table-cell">{{ trans('ratting-manager::ratter.days') }}</th>
                            <th class="d-none d-lg-table-cell">{{ trans('ratting-manager::dashboard.last_tick') }}</th>
                            <th>{{ trans('ratting-manager::ratter.wallet') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr data-wallet="{{ $row['wallet_token'] ? 1 : 0 }}">
                                <td data-order="{{ $row['name'] }}" data-search="{{ $row['name'] }}">
                                    <span class="d-inline-flex align-items-center" style="gap: 10px">
                                        <img class="rm-portrait rm-portrait-sm" src="https://images.evetech.net/characters/{{ $row['main_id'] }}/portrait?size=64" alt="" loading="lazy">
                                        <span class="d-flex flex-column">
                                            <a href="{{ route('ratting-manager.ratters.show', $row['main_id']) }}">{{ $row['name'] }}</a>
                                            <small class="text-muted">{{ trans_choice('ratting-manager::ratter.characters_count', count($row['characters']), ['count' => count($row['characters'])]) }}</small>
                                        </span>
                                    </span>
                                </td>
                                <td class="text-right rm-isk" data-order="{{ $row['value'] }}">@rmisk($row['value'])</td>
                                <td class="text-right rm-tax" data-order="{{ $row['corp_tax'] }}">@rmisk($row['corp_tax'])</td>
                                <td class="text-right" data-order="{{ $row['minutes'] }}">{{ \RattingManager\Support\Format::hours($row['minutes']) }}</td>
                                <td class="text-right d-none d-md-table-cell" data-order="{{ $row['value_per_hour'] }}">@rmisk($row['value_per_hour'])</td>
                                <td class="text-right d-none d-lg-table-cell">{{ $row['active_days'] }}</td>
                                <td class="d-none d-lg-table-cell" data-order="{{ $row['last_tick'] }}">{{ $row['last_tick'] ? substr($row['last_tick'], 0, 16) : '-' }}</td>
                                <td data-order="{{ $row['wallet_token'] ? 1 : 0 }}">
                                    @if($row['wallet_token'])
                                        <span class="rm-wallet rm-wallet-linked">{{ trans('ratting-manager::ratter.linked') }}</span>
                                    @else
                                        <span class="rm-wallet rm-wallet-not">{{ trans('ratting-manager::ratter.not_linked') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

@section('rm-after')
    @include('ratting-manager::partials.character-lookup')
@endsection


@push('javascript')
@include('ratting-manager::partials.datatables')
<script>
$(function () {
    if (!$.fn.DataTable || !document.getElementById('rm-ratters')) {
        return;
    }

    // The wallet chips filter on each row's data-wallet.
    var wallet = '';
    $.fn.dataTable.ext.search.push(function (settings, data, index) {
        if (settings.nTable.id !== 'rm-ratters' || wallet === '') {
            return true;
        }
        return settings.aoData[index].nTr.getAttribute('data-wallet') === wallet;
    });

    var table = $('#rm-ratters').DataTable({ order: [[1, 'desc']], pageLength: 50 });

    $('#rm-wallet-filter [data-rm-wallet]').on('click', function () {
        wallet = String($(this).data('rm-wallet'));
        $('#rm-wallet-filter [data-rm-wallet]').attr('aria-pressed', 'false');
        $(this).attr('aria-pressed', 'true');
        table.draw();
    });
});
</script>
@endpush
