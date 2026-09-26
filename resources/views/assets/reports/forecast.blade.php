@extends('layouts.erp')

@section('title', __('Depreciation Forecast'))

@section('content_header')
    <h1>{{ __('Depreciation Forecast') }}</h1>
    <p class="text-body-secondary mb-0">{{ __('Depreciation each asset in use will be charged in the next 12 months, on its current policy.') }}</p>
@endsection

@section('content')
    <x-print-toolbar />
    <x-report-letterhead :title="__('Depreciation Forecast')" :subtitle="__('12 months from :date', ['date' => Formatter::date($start)])" />
    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Asset') }}</th>
                        @foreach ($forecast['months'] as $month)<th class="text-end text-nowrap">{{ \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $month.'-01')->translatedFormat('M y') }}</th>@endforeach
                        <th class="text-end">{{ __('Total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($forecast['rows'] as $row)
                        <tr>
                            <td class="text-nowrap"><a href="{{ route('assets.assets.show', $row['asset']->id) }}">{{ $row['asset']->asset_number }}</a> {{ $row['asset']->name }}</td>
                            @foreach ($forecast['months'] as $month)<td class="text-end">{{ bccomp($row['charges'][$month], '0', 4) > 0 ? Formatter::amount($row['charges'][$month]) : '—' }}</td>@endforeach
                            <td class="text-end fw-bold">{{ Formatter::amount($row['total']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($forecast['months']) + 2 }}" class="text-center text-body-secondary py-4">{{ __('No asset has depreciation ahead.') }}</td></tr>
                    @endforelse
                </tbody>
                @if ($forecast['rows']->isNotEmpty())
                    <tfoot>
                        <tr class="fw-bold">
                            <td>{{ __('Total') }}</td>
                            @foreach ($forecast['months'] as $month)<td class="text-end">{{ Formatter::amount($forecast['rows']->reduce(fn ($sum, $row) => bcadd($sum, $row['charges'][$month], 4), '0')) }}</td>@endforeach
                            <td class="text-end">{{ Formatter::amount($forecast['rows']->reduce(fn ($sum, $row) => bcadd($sum, $row['total'], 4), '0')) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
