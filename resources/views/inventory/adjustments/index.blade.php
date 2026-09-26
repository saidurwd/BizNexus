@extends('layouts.erp')

@section('title', __('Stock Adjustments'))

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="m-0">{{ __('Stock Adjustments') }}</h1>
        @can('inventory.adjustments.create')
            <div class="d-flex gap-2">
                <a href="{{ route('inventory.adjustments.create', ['reason' => 'count']) }}" class="btn btn-outline-primary"><i class="bi bi-clipboard-check"></i> {{ __('New stock count') }}</a>
                <a href="{{ route('inventory.adjustments.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> {{ __('New adjustment') }}</a>
            </div>
        @endcan
    </div>
@endsection

@section('content')
    <x-finance.list-filters :filters="$filters" :statuses="['DRAFT', 'POSTED']" :search-label="__('Number or notes')" />

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Number') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Reason') }}</th>
                        <th>{{ __('Warehouse') }}</th>
                        <th class="text-end">{{ __('Lines') }}</th>
                        <th>{{ __('Created by') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($adjustments as $adjustment)
                        <tr>
                            <td><a href="{{ route('inventory.adjustments.show', $adjustment->id) }}">{{ $adjustment->adjustment_number }}</a></td>
                            <td>{{ Formatter::date($adjustment->adjustment_date) }}</td>
                            <td>{{ $adjustment->reasonLabel() }}</td>
                            <td>{{ $adjustment->warehouse?->code }}</td>
                            <td class="text-end">{{ $adjustment->lines_count }}</td>
                            <td>{{ $adjustment->createdBy?->name }}</td>
                            <td><x-status-badge :status="$adjustment->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-body-secondary py-4">{{ __('No stock adjustments found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($adjustments->hasPages())
            <div class="card-footer">{{ $adjustments->links() }}</div>
        @endif
    </div>
@endsection
