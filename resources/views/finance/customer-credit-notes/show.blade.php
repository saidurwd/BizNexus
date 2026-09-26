@extends('layouts.erp')

@php $currencyCode = $creditNote->currency?->code; @endphp

@section('title', __('Credit note :number', ['number' => $creditNote->note_number]))

@section('content_header')
    <div class="d-flex flex-wrap align-items-center gap-2">
        <h1 class="m-0">{{ __('Credit note :number', ['number' => $creditNote->note_number]) }}</h1>
        <x-status-badge :status="$creditNote->status" class="fs-6" />
    </div>
@endsection

@section('content')
    @if ($creditNote->rejection_reason)
        <div class="alert alert-warning">{{ __('Rejected: :reason', ['reason' => $creditNote->rejection_reason]) }}</div>
    @endif

    <div class="row">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">{{ __('Customer') }}</dt>
                        <dd class="col-sm-8">{{ $creditNote->customer?->name }}</dd>
                        <dt class="col-sm-4">{{ __('Date') }}</dt>
                        <dd class="col-sm-8">{{ Formatter::date($creditNote->note_date) }}</dd>
                        <dt class="col-sm-4">{{ __('Invoice credited') }}</dt>
                        <dd class="col-sm-8">
                            @if ($creditNote->invoice)
                                <a href="{{ route('finance.customer-invoices.show', $creditNote->invoice->id) }}">{{ $creditNote->invoice->invoice_number }}</a>
                            @else
                                {{ __('None (credit on account)') }}
                            @endif
                        </dd>
                        <dt class="col-sm-4">{{ __('Currency') }}</dt>
                        <dd class="col-sm-8">{{ $currencyCode ?? __('Company currency') }}@if ($creditNote->currency) · {{ __('rate :rate', ['rate' => Formatter::rate($creditNote->exchange_rate)]) }}@endif</dd>
                        <dt class="col-sm-4">{{ __('Reason') }}</dt>
                        <dd class="col-sm-8">{{ $creditNote->description }}</dd>
                        @if ($creditNote->journal)
                            <dt class="col-sm-4">{{ __('Journal') }}</dt>
                            <dd class="col-sm-8"><a href="{{ route('finance.journals.show', $creditNote->journal->id) }}">{{ $creditNote->journal->journal_number }}</a></dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card mb-3">
                <table class="table table-sm mb-0">
                    <tr><th>{{ __('Net') }}</th><td class="text-end">{{ Formatter::amount($creditNote->subtotal, $currencyCode) }}</td></tr>
                    <tr><th>{{ __('Tax') }}</th><td class="text-end">{{ Formatter::amount($creditNote->tax_amount, $currencyCode) }}</td></tr>
                    <tr class="fw-bold"><th>{{ __('Total credit') }}</th><td class="text-end">{{ Formatter::amount($creditNote->total_amount, $currencyCode) }}</td></tr>
                    @if ($creditNote->isPosted())
                        <tr><th>{{ __('Applied to the invoice') }}</th><td class="text-end">{{ Formatter::amount($creditNote->applied_amount, $currencyCode) }}</td></tr>
                        <tr><th>{{ __('Unapplied credit') }}</th><td class="text-end">{{ Formatter::amount($creditNote->unappliedAmount(), $currencyCode) }}</td></tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body table-responsive p-0">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Account') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th class="text-end">{{ __('Qty') }}</th>
                        <th class="text-end">{{ __('Unit price') }}</th>
                        <th>{{ __('Tax code') }}</th>
                        <th class="text-end">{{ __('Net') }}</th>
                        <th class="text-end">{{ __('Tax') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($creditNote->lines as $line)
                        <tr>
                            <td>{{ $line->account?->account_code }} {{ $line->account?->account_name }}</td>
                            <td>{{ $line->description }}</td>
                            <td class="text-end">{{ Formatter::number($line->quantity, 2) }}</td>
                            <td class="text-end">{{ Formatter::amount($line->unit_price, $currencyCode) }}</td>
                            <td>{{ $line->tax?->tax_code ?? '—' }}</td>
                            <td class="text-end">{{ Formatter::amount($line->subtotal, $currencyCode) }}</td>
                            <td class="text-end">{{ Formatter::amount($line->tax_amount, $currencyCode) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('finance.customer-credit-notes.index') }}" class="btn btn-secondary">{{ __('Back') }}</a>
        <a href="{{ route('finance.customer-credit-notes.pdf', $creditNote->id) }}" class="btn btn-outline-secondary" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf"></i> {{ __('PDF') }}</a>

        @if (in_array($creditNote->status, ['DRAFT', 'REJECTED'], true))
            @can('finance.customer-credit-notes.update')
                <a href="{{ route('finance.customer-credit-notes.edit', $creditNote->id) }}" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> {{ __('Edit') }}</a>
            @endcan
        @endif

        @if ($creditNote->isDraft())
            @can('finance.customer-credit-notes.submit')
                <form method="POST" action="{{ route('finance.customer-credit-notes.submit', $creditNote->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-success"><i class="bi bi-send"></i> {{ __('Submit for approval') }}</button>
                </form>
            @endcan
        @endif

        @if ($creditNote->isSubmitted())
            @can('finance.customer-credit-notes.approve')
                <form method="POST" action="{{ route('finance.customer-credit-notes.approve', $creditNote->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-circle"></i> {{ __('Approve') }}</button>
                </form>
            @endcan
            @can('finance.customer-credit-notes.reject')
                <form method="POST" action="{{ route('finance.customer-credit-notes.reject', $creditNote->id) }}" class="d-flex gap-2">
                    @csrf
                    <input type="text" name="reason" class="form-control" placeholder="{{ __('Reason for rejecting') }}" maxlength="1000">
                    <button type="submit" class="btn btn-outline-danger">{{ __('Reject') }}</button>
                </form>
            @endcan
        @endif

        @if ($creditNote->isApproved())
            @can('finance.customer-credit-notes.post')
                <form method="POST" action="{{ route('finance.customer-credit-notes.post', $creditNote->id) }}" onsubmit="return confirm(@js(__('Post this credit note to the ledger?')))">
                    @csrf
                    <button type="submit" class="btn btn-primary"><i class="bi bi-journal-check"></i> {{ __('Post') }}</button>
                </form>
            @endcan
        @endif

        @if (! in_array($creditNote->status, ['POSTED', 'CANCELLED'], true))
            @can('finance.customer-credit-notes.cancel')
                <form method="POST" action="{{ route('finance.customer-credit-notes.cancel', $creditNote->id) }}" onsubmit="return confirm(@js(__('Cancel this credit note?')))">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger">{{ __('Cancel credit note') }}</button>
                </form>
            @endcan
        @endif
    </div>
@endsection
