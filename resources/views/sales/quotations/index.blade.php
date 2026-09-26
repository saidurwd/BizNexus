@extends('layouts.erp')

@section('title', __('Quotations'))

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="m-0">{{ __('Quotations') }}</h1>
        @can('sales.quotations.manage')
            <a href="{{ route('sales.quotations.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> {{ __('New quotation') }}</a>
        @endcan
    </div>
@endsection

@section('content')
    <x-finance.list-filters :filters="$filters" :statuses="\Modules\Sales\Models\Quotation::STATUSES" :search-label="__('Number, customer reference or customer')" />

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Number') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Valid until') }}</th>
                        <th class="text-end">{{ __('Total') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($quotations as $quotation)
                        <tr>
                            <td><a href="{{ route('sales.quotations.show', $quotation->id) }}">{{ $quotation->quotation_number }}</a></td>
                            <td>{{ Formatter::date($quotation->quotation_date) }}</td>
                            <td>{{ $quotation->customer?->name }}</td>
                            <td>
                                {{ $quotation->valid_until ? Formatter::date($quotation->valid_until) : '—' }}
                                @if ($quotation->isExpired())<span class="badge text-bg-warning">{{ __('Expired') }}</span>@endif
                            </td>
                            <td class="text-end">{{ Formatter::amount($quotation->total_amount, $quotation->currency?->code) }} <small class="text-body-secondary">{{ $quotation->currency?->code }}</small></td>
                            <td><x-status-badge :status="$quotation->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">{{ __('No quotations found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($quotations->hasPages())
            <div class="card-footer">{{ $quotations->links() }}</div>
        @endif
    </div>
@endsection
