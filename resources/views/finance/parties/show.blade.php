@extends('layouts.erp')

@php
    $type = $isCustomer ? 'customers' : 'suppliers';
    $documentRoute = $isCustomer ? 'finance.customer-invoices' : 'finance.supplier-invoices';
    $code = $isCustomer ? $party->customer_code : $party->supplier_code;
    $invoices = $party->invoices()->with('currency')->latest('invoice_date')->limit(10)->get();
@endphp

@section('title', $party->name)

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="m-0">{{ $party->name }} <small class="text-body-secondary">{{ $code }}</small> <x-status-badge :status="$party->status" class="fs-6" /></h1>
        <div class="d-flex gap-2">
            @can("finance.{$type}.update")
                <a href="{{ route("finance.{$type}.edit", $party->id) }}" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> {{ __('Edit') }}</a>
            @endcan
            <a href="{{ route($isCustomer ? 'finance.customer-statements.show' : 'finance.supplier-statements.show', $party->id) }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-person"></i> {{ __('Statement') }}</a>
            @can(($isCustomer ? 'finance.customer-invoices' : 'finance.supplier-invoices').'.create')
                <a href="{{ route("{$documentRoute}.create") }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> {{ $isCustomer ? __('New invoice') : __('Record invoice') }}</a>
            @endcan
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">{{ __('Contact') }}</dt>
                        <dd class="col-sm-8">{{ $party->contact_person ?? '—' }} @if ($party->email)<a href="mailto:{{ $party->email }}">{{ $party->email }}</a>@endif {{ $party->phone }}</dd>
                        <dt class="col-sm-4">{{ __('Tax number') }}</dt>
                        <dd class="col-sm-8">{{ $party->tax_number ?? '—' }}</dd>
                        <dt class="col-sm-4">{{ __('Country') }}</dt>
                        <dd class="col-sm-8">{{ $party->country_code ? (\Modules\Core\Support\Countries::options()[$party->country_code] ?? $party->country_code) : '—' }}</dd>
                        <dt class="col-sm-4">{{ __('Address') }}</dt>
                        <dd class="col-sm-8">{!! nl2br(e($party->address ?? '—')) !!}</dd>
                        <dt class="col-sm-4">{{ __('Currency') }}</dt>
                        <dd class="col-sm-8">{{ $party->currency?->code ?? __('Company currency') }}</dd>
                        <dt class="col-sm-4">{{ __('Payment term') }}</dt>
                        <dd class="col-sm-8">{{ $party->paymentTerm?->name ?? __('Due on the invoice date') }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card mb-3">
                <table class="table table-sm mb-0">
                    <tr>
                        <th>{{ $isCustomer ? __('Owed by the customer') : __('Owed to the supplier') }}</th>
                        <td class="text-end fw-bold">{{ Formatter::amount($openBalance) }}</td>
                    </tr>
                    @if ($isCustomer)
                        <tr>
                            <th>{{ __('Credit limit') }}</th>
                            <td class="text-end">{{ $party->credit_limit !== null ? Formatter::amount($party->credit_limit) : __('No limit') }}</td>
                        </tr>
                        @if ($availableCredit !== null)
                            <tr class="{{ bccomp($availableCredit, '0', 4) < 0 ? 'table-danger' : '' }}">
                                <th>{{ __('Available credit') }}</th>
                                <td class="text-end">{{ Formatter::amount($availableCredit) }}</td>
                            </tr>
                        @endif
                    @endif
                </table>
                <div class="card-footer small text-body-secondary">{{ __('Posted, unpaid invoices in the functional currency.') }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Latest invoices') }}</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr><th>{{ __('Number') }}</th><th>{{ __('Date') }}</th><th>{{ __('Due') }}</th><th class="text-end">{{ __('Total') }}</th><th class="text-end">{{ __('Outstanding') }}</th><th>{{ __('Status') }}</th></tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td><a href="{{ route("{$documentRoute}.show", $invoice->id) }}">{{ $invoice->invoice_number }}</a></td>
                            <td>{{ Formatter::date($invoice->invoice_date) }}</td>
                            <td>{{ Formatter::date($invoice->due_date) }}</td>
                            <td class="text-end">{{ Formatter::amount($invoice->total_amount, $invoice->currency?->code) }} <small class="text-body-secondary">{{ $invoice->currency?->code }}</small></td>
                            <td class="text-end">{{ Formatter::amount($invoice->outstanding_amount, $invoice->currency?->code) }}</td>
                            <td><x-status-badge :status="$invoice->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary py-3">{{ __('No invoices yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
