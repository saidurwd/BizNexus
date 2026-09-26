@extends('layouts.erp')

@section('title', __('Stock Transfers'))

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="m-0">{{ __('Stock Transfers') }}</h1>
        @can('inventory.transfers.create')
            <a href="{{ route('inventory.transfers.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> {{ __('New transfer') }}</a>
        @endcan
    </div>
@endsection

@section('content')
    <x-finance.list-filters :filters="$filters" :search-label="__('Number or notes')" />

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Number') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('From') }}</th>
                        <th>{{ __('To') }}</th>
                        <th class="text-end">{{ __('Lines') }}</th>
                        <th>{{ __('Created by') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transfers as $transfer)
                        <tr>
                            <td><a href="{{ route('inventory.transfers.show', $transfer->id) }}">{{ $transfer->transfer_number }}</a></td>
                            <td>{{ Formatter::date($transfer->transfer_date) }}</td>
                            <td>{{ $transfer->fromWarehouse?->code }}</td>
                            <td>{{ $transfer->toWarehouse?->code }}</td>
                            <td class="text-end">{{ $transfer->lines_count }}</td>
                            <td>{{ $transfer->createdBy?->name }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">{{ __('No stock transfers found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($transfers->hasPages())
            <div class="card-footer">{{ $transfers->links() }}</div>
        @endif
    </div>
@endsection
