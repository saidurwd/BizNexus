@extends('layouts.erp')

@section('title', __('Asset Movements'))

@section('content_header')
    <h1>{{ __('Asset Movements') }}</h1>
    <p class="text-body-secondary mb-0">{{ __('Reconciliation of cost and accumulated depreciation by category for the period (IAS 16.73).') }}</p>
@endsection

@php
    $columns = [
        'cost_opening' => __('Cost at start'), 'additions' => __('Additions'), 'disposals' => __('Disposals'), 'cost_closing' => __('Cost at end'),
        'depreciation_opening' => __('Depreciation at start'), 'depreciation' => __('Charge for the period'), 'impairment' => __('Impairment'),
        'depreciation_disposed' => __('On disposals'), 'depreciation_closing' => __('Depreciation at end'),
        'book_value_opening' => __('Carrying amount at start'), 'book_value_closing' => __('Carrying amount at end'),
    ];
@endphp

@section('content')
    <form method="GET" class="card mb-3 d-print-none">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-3"><label for="from" class="form-label">{{ __('From') }}</label><input type="date" id="from" name="from" value="{{ $from->toDateString() }}" class="form-control"></div>
            <div class="col-md-3"><label for="to" class="form-label">{{ __('To') }}</label><input type="date" id="to" name="to" value="{{ $to->toDateString() }}" class="form-control"></div>
            <div class="col-auto"><button type="submit" class="btn btn-primary">{{ __('View report') }}</button></div>
            <div class="col-auto"><button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> {{ __('Print') }}</button></div>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <x-report-letterhead :title="__('Asset Movements')" :subtitle="__(':from to :to', ['from' => Formatter::date($from), 'to' => Formatter::date($to)])" />
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th></th>@foreach ($rows as $row)<th class="text-end">{{ $row['category']->name }}</th>@endforeach<th class="text-end">{{ __('Total') }}</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($columns as $key => $label)
                            <tr class="{{ in_array($key, ['cost_closing', 'depreciation_closing', 'book_value_closing'], true) ? 'fw-bold' : '' }}">
                                <td>{{ $label }}</td>
                                @foreach ($rows as $row)<td class="text-end">{{ Formatter::amount($row[$key]) }}</td>@endforeach
                                <td class="text-end">{{ Formatter::amount($rows->reduce(fn ($sum, $row) => bcadd($sum, $row[$key], 4), '0')) }}</td>
                            </tr>
                        @empty
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($rows->isEmpty())
                <p class="text-center text-body-secondary py-3 mb-0">{{ __('No asset movements in this period.') }}</p>
            @endif
        </div>
    </div>
@endsection
