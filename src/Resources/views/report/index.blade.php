@extends('ratting-manager::layouts.page')

@section('title', trans('ratting-manager::report.title'))
@section('page_header', trans('ratting-manager::report.title'))

@section('rm-tabs')
    @include('ratting-manager::partials.page-tabs', ['icon' => 'fa-file-invoice', 'label' => trans('ratting-manager::menu.report')])
@endsection

@section('rm-body')
    @if($notConfigured)
        @include('ratting-manager::partials.not-configured')
    @else
        @php $fmt = \RattingManager\Support\Format::class; @endphp

        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-invoice"></i> {{ trans('ratting-manager::report.heading', ['month' => $period->label()]) }}</h3>
                <div class="card-tools">
                    <form method="GET" action="{{ route('ratting-manager.report') }}" class="rm-filters">
                        @if(count($corporations) > 1)
                            <select name="corporation" class="form-control form-control-sm" aria-label="{{ trans('ratting-manager::common.corporation') }}">
                                <option value="">{{ trans('ratting-manager::common.all_corporations') }}</option>
                                @foreach($corporations as $id => $name)
                                    <option value="{{ $id }}" @if($pickedCorporation === $id && count($corporations) > 1) selected @endif>{{ $name }}</option>
                                @endforeach
                            </select>
                        @endif
                        <select name="month" class="form-control form-control-sm" aria-label="{{ trans('ratting-manager::report.month') }}">
                            @foreach($choices as $key => $label)
                                <option value="{{ $key }}" @if($period->key === $key) selected @endif>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-rm-primary">{{ trans('ratting-manager::common.apply') }}</button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">{{ trans('ratting-manager::report.intro') }}</p>
                <div class="row">
                    @foreach([
                        ['isk_ratted', $fmt::isk($totals['gross'] ?? 0), 'fa-coins', 'bg-gradient-success'],
                        ['ratting_tax', $fmt::isk($totals['corp_tax']), 'fa-landmark', 'bg-gradient-danger'],
                        ['alliance_tax', ($totals['alliance_rate'] ?? 0) > 0 ? $fmt::isk($totals['alliance_tax']) : '-', 'fa-flag', 'bg-gradient-info'],
                        ['active_members', number_format($totals['players']), 'fa-users', 'bg-gradient-primary'],
                    ] as [$label, $value, $icon, $colour])
                        <div class="col-lg-3 col-md-6">
                            <div class="info-box {{ $colour }}">
                                <span class="info-box-icon"><i class="fas {{ $icon }}"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">{{ trans('ratting-manager::report.' . $label) }}</span>
                                    <span class="info-box-number">{{ $value }}</span>
                                    <small>{{ trans('ratting-manager::report.' . $label . '_hint', ['rate' => rtrim(rtrim(number_format(($totals['alliance_rate'] ?? 0) * 100, 2), '0'), '.')]) }}</small>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="text-muted mb-0">
                    <i class="fas fa-globe"></i>
                    {{ trans('ratting-manager::dashboard.space_counts', ['systems' => number_format($space['systems']), 'constellations' => number_format($space['constellations']), 'regions' => number_format($space['regions'])]) }}
                </p>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4">
                <div class="card card-dark">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-map-marker-alt"></i> {{ trans('ratting-manager::report.top_systems', ['count' => \RattingManager\Http\Controllers\ReportController::TOP_SYSTEMS]) }}</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ trans('ratting-manager::dashboard.system') }}</th>
                                    <th class="text-right">{{ trans('ratting-manager::report.isk_ratted') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($systems as $i => $s)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>
                                            {{ $s['name'] }}
                                            @if($s['security'] !== null)
                                                <small class="text-muted">{{ number_format($s['security'], 1) }}</small>
                                            @endif
                                        </td>
                                        <td class="text-right">@rmisk($s['value'])</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">{{ trans('ratting-manager::common.no_data') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card card-dark">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-users"></i> {{ trans('ratting-manager::report.members', ['count' => count($members)]) }}</h3>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ trans('ratting-manager::report.member') }}</th>
                                    <th class="text-right">{{ trans('ratting-manager::report.isk_ratted') }}</th>
                                    <th class="text-right">{{ trans('ratting-manager::report.ratting_tax') }}</th>
                                    <th class="text-right d-none d-md-table-cell">{{ trans('ratting-manager::dashboard.hours') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($members as $i => $m)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td><a href="{{ route('ratting-manager.ratters.show', ['characterId' => $m['main_id'], 'period' => $period->key]) }}">{{ $m['name'] }}</a></td>
                                        <td class="text-right">@rmisk($m['gross'])</td>
                                        <td class="text-right">@rmisk($m['corp_tax'])</td>
                                        <td class="text-right d-none d-md-table-cell">{{ $fmt::hours($m['minutes']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">{{ trans('ratting-manager::common.no_data') }}</td></tr>
                                @endforelse
                            </tbody>
                            @if(count($members) > 0)
                                <tfoot>
                                    <tr class="rm-total-row">
                                        <td></td>
                                        <td>{{ trans('ratting-manager::insights.total') }}</td>
                                        <td class="text-right">@rmisk($totals['gross'] ?? 0)</td>
                                        <td class="text-right">@rmisk($totals['corp_tax'])</td>
                                        <td class="text-right d-none d-md-table-cell">{{ $fmt::hours((int) $totals['minutes']) }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('rm-after')
    @include('ratting-manager::partials.character-lookup')
@endsection
