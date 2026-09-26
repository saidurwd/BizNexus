@extends('layouts.erp')

@section('title', __('Customer Invoices'))

@section('content_header')
    <h1>{{ __('Customer Invoices') }}</h1>
    <div class="mt-2">
        <a href="{{ route('finance.customer-invoices.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> {{ __('New Invoice') }}
        </a>
    </div>
@endsection

@section('content')
    <x-finance.list-filters :filters="$filters" :statuses="['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED', 'POSTED', 'PARTIALLY_PAID', 'PAID', 'CANCELLED']" :search-label="__('Invoice number, description or customer')" with-overdue />
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Customer') }}</th>
                        <th class="text-end">{{ __('Amount') }}</th>
                        <th class="text-end">{{ __('Tax') }}</th>
                        <th class="text-end">{{ __('Total') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_number }}</td>
                            <td>{{ $invoice->invoice_date->format('Y-m-d') }}</td>
                            <td>{{ $invoice->customer?->name ?? '-' }}</td>
                            <td class="text-end">{{ Formatter::amount($invoice->subtotal, $invoice->currency?->code) }}</td>
                            <td class="text-end">{{ Formatter::amount($invoice->tax_amount, $invoice->currency?->code) }}</td>
                            <td class="text-end">{{ Formatter::amount($invoice->total_amount, $invoice->currency?->code) }}</td>
                            <td>
                                <span class="badge bg-{{ $invoice->status === 'PAID' ? 'success' : ($invoice->status === 'APPROVED' ? 'info' : ($invoice->status === 'DRAFT' ? 'secondary' : 'warning')) }}">
                                    {{ $invoice->status }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('finance.customer-invoices.show', $invoice->id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">{{ __('No invoices found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($invoices->hasPages())
            <div class="card-footer">{{ $invoices->links() }}</div>
        @endif
    </div>
@endsection
