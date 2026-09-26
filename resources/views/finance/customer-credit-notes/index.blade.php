@extends('layouts.erp')

@section('title', __('Customer credit notes'))

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="m-0">{{ __('Customer credit notes') }}</h1>
        @can('finance.customer-credit-notes.create')
            <a href="{{ route('finance.customer-credit-notes.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> {{ __('New credit note') }}</a>
        @endcan
    </div>
@endsection

@section('content')
    <x-finance.list-filters :filters="$filters" :statuses="['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED', 'POSTED', 'CANCELLED']" :search-label="__('Credit note number, reason or customer')" />

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ __('Number') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Invoice credited') }}</th>
                        <th class="text-end">{{ __('Total') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($creditNotes as $creditNote)
                        <tr>
                            <td><a href="{{ route('finance.customer-credit-notes.show', $creditNote->id) }}">{{ $creditNote->note_number }}</a></td>
                            <td>{{ Formatter::date($creditNote->note_date) }}</td>
                            <td>{{ $creditNote->customer?->name }}</td>
                            <td>{{ $creditNote->invoice?->invoice_number ?? '—' }}</td>
                            <td class="text-end">{{ Formatter::amount($creditNote->total_amount, $creditNote->currency?->code) }} <small class="text-body-secondary">{{ $creditNote->currency?->code }}</small></td>
                            <td><x-status-badge :status="$creditNote->status" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-body-secondary py-4">{{ __('No credit notes yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($creditNotes->hasPages())
            <div class="card-footer">{{ $creditNotes->links() }}</div>
        @endif
    </div>
@endsection
