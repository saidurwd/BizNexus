@extends('layouts.erp')

@section('title', __('Fixed Asset Register'))

@section('content_header')
    <h1>{{ __('Fixed Asset Register') }}</h1>
@endsection

@section('content')
    <form method="GET" class="card mb-3 d-print-none">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-3"><label for="date" class="form-label">{{ __('As of') }}</label><input type="date" id="date" name="date" value="{{ $date->toDateString() }}" class="form-control"></div>
            <div class="col-md-3"><label for="category" class="form-label">{{ __('Category') }}</label>
                <select id="category" name="category" class="form-select"><option value="">{{ __('All') }}</option>@foreach ($categories as $option)<option value="{{ $option->id }}" @selected((string) $category === (string) $option->id)>{{ $option->name }}</option>@endforeach</select>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-primary">{{ __('View report') }}</button></div>
            <div class="col-auto"><button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> {{ __('Print') }}</button></div>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <x-report-letterhead :title="__('Fixed Asset Register')" :subtitle="__('As of :date', ['date' => Formatter::date($date)])" />
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>{{ __('Number') }}</th><th>{{ __('Name') }}</th><th>{{ __('Category') }}</th><th>{{ __('Location') }}</th><th>{{ __('Acquired') }}</th><th class="text-end">{{ __('Cost') }}</th><th class="text-end">{{ __('Accumulated depreciation') }}</th><th class="text-end">{{ __('Carrying amount') }}</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $row['asset']->asset_number }}</td>
                                <td>{{ $row['asset']->name }}</td>
                                <td>{{ $row['asset']->category?->name }}</td>
                                <td class="small">{{ collect([$row['asset']->branch?->name, $row['asset']->location])->filter()->implode(' · ') ?: '—' }}</td>
                                <td>{{ Formatter::date($row['asset']->acquisition_date) }}</td>
                                <td class="text-end">{{ Formatter::amount($row['cost']) }}</td>
                                <td class="text-end">{{ Formatter::amount($row['accumulated']) }}</td>
                                <td class="text-end">{{ Formatter::amount($row['book_value']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-body-secondary py-4">{{ __('No assets held on this date.') }}</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="5">{{ __('Total') }}</td>
                            <td class="text-end">{{ Formatter::amount($rows->reduce(fn ($sum, $row) => bcadd($sum, $row['cost'], 4), '0')) }}</td>
                            <td class="text-end">{{ Formatter::amount($rows->reduce(fn ($sum, $row) => bcadd($sum, $row['accumulated'], 4), '0')) }}</td>
                            <td class="text-end">{{ Formatter::amount($rows->reduce(fn ($sum, $row) => bcadd($sum, $row['book_value'], 4), '0')) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
