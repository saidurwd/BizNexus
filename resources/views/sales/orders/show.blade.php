@extends('layouts.erp')

@php $currencyCode = $order->currencyCode(); @endphp

@section('title', __('Sales order :number', ['number' => $order->order_number]))

@section('content_header')
    <div class="d-flex flex-wrap align-items-center gap-2">
        <h1 class="m-0">{{ __('Sales order :number', ['number' => $order->order_number]) }}</h1>
        <x-status-badge :status="$order->status" class="fs-6" />
    </div>
@endsection

@section('content')
    <x-print-toolbar />
    <x-print-document-header :title="__('Sales order')" :subtitle="$order->order_number" />

    <div class="card mb-3">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">{{ __('Customer') }}</dt>
                <dd class="col-sm-9"><a href="{{ route('finance.customers.show', $order->customer_id) }}">{{ $order->customer?->name }}</a></dd>
                <dt class="col-sm-3">{{ __('Customer reference') }}</dt>
                <dd class="col-sm-9">{{ $order->customer_reference ?? '—' }}</dd>
                <dt class="col-sm-3">{{ __('Order date') }}</dt>
                <dd class="col-sm-9">{{ Formatter::date($order->order_date) }}</dd>
                <dt class="col-sm-3">{{ __('Delivery date') }}</dt>
                <dd class="col-sm-9">{{ $order->delivery_date ? Formatter::date($order->delivery_date) : '—' }}</dd>
                <dt class="col-sm-3">{{ __('Ship from') }}</dt>
                <dd class="col-sm-9">{{ $order->warehouse?->code }} — {{ $order->warehouse?->name }}</dd>
                @if ($order->quotation)
                    <dt class="col-sm-3">{{ __('Quotation') }}</dt>
                    <dd class="col-sm-9"><a href="{{ route('sales.quotations.show', $order->quotation->id) }}">{{ $order->quotation->quotation_number }}</a></dd>
                @endif
                <dt class="col-sm-3">{{ __('Created by') }}</dt>
                <dd class="col-sm-9">{{ $order->createdBy?->name ?? '—' }}</dd>
                @if ($order->confirmedBy)
                    <dt class="col-sm-3">{{ __('Confirmed by') }}</dt>
                    <dd class="col-sm-9">{{ $order->confirmedBy->name }} · {{ Formatter::date($order->confirmed_at) }}</dd>
                @endif
                @if ($order->notes)
                    <dt class="col-sm-3">{{ __('Notes') }}</dt>
                    <dd class="col-sm-9">{!! nl2br(e($order->notes)) !!}</dd>
                @endif
            </dl>
        </div>
    </div>

    @include('sales._document-lines', ['document' => $order, 'progress' => true])

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('sales.orders.index') }}" class="btn btn-secondary">{{ __('Back') }}</a>
        <a href="{{ route('sales.orders.pdf', $order->id) }}" class="btn btn-outline-secondary" target="_blank"><i class="bi bi-filetype-pdf"></i> {{ __('PDF') }}</a>

        @if ($order->status === 'DRAFT')
            @can('sales.orders.create')
                <a href="{{ route('sales.orders.edit', $order->id) }}" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> {{ __('Edit') }}</a>
                <form method="POST" action="{{ route('sales.orders.destroy', $order->id) }}" onsubmit="return confirm(@js(__('Delete this sales order?')))">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i> {{ __('Delete') }}</button>
                </form>
            @endcan
            @can('sales.orders.confirm')
                <form method="POST" action="{{ route('sales.orders.confirm', $order->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-circle"></i> {{ __('Confirm order') }}</button>
                </form>
            @endcan
        @endif

        @if ($order->canDeliver())
            @can('sales.deliveries.create')
                <a href="{{ route('sales.deliveries.create', $order->id) }}" class="btn btn-primary"><i class="bi bi-truck"></i> {{ __('Deliver') }}</a>
            @endcan
        @endif

        @if ($order->canInvoice())
            @can('finance.customer-invoices.create')
                <a href="{{ route('sales.orders.invoice.create', $order->id) }}" class="btn btn-primary"><i class="bi bi-receipt"></i> {{ __('Create invoice') }}</a>
            @endcan
        @endif

        @can('sales.orders.cancel')
            @if (in_array($order->status, ['DRAFT', 'CONFIRMED'], true))
                <form method="POST" action="{{ route('sales.orders.cancel', $order->id) }}" onsubmit="return confirm(@js(__('Cancel this sales order?')))">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger">{{ __('Cancel order') }}</button>
                </form>
            @endif
            @if ($order->isOpen())
                <form method="POST" action="{{ route('sales.orders.close', $order->id) }}" onsubmit="return confirm(@js(__('Close this order? Quantities not yet delivered will not be delivered.')))">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary">{{ __('Close order') }}</button>
                </form>
            @endif
        @endcan
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ __('Deliveries') }}</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        @forelse ($order->deliveries as $delivery)
                            <tr>
                                <td><a href="{{ route('sales.deliveries.show', $delivery->id) }}">{{ $delivery->delivery_number }}</a></td>
                                <td>{{ Formatter::date($delivery->delivery_date) }}</td>
                                <td>{{ $delivery->carrier }} {{ $delivery->tracking_number }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-body-secondary">{{ __('Nothing delivered yet.') }}</td></tr>
                        @endforelse
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ __('Invoices') }}</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        @forelse ($order->invoices as $invoice)
                            <tr>
                                <td><a href="{{ route('finance.customer-invoices.show', $invoice->id) }}">{{ $invoice->invoice_number }}</a></td>
                                <td>{{ Formatter::date($invoice->invoice_date) }}</td>
                                <td class="text-end">{{ Formatter::amount($invoice->total_amount, $currencyCode) }}</td>
                                <td><x-status-badge :status="$invoice->status" /></td>
                            </tr>
                        @empty
                            <tr><td class="text-body-secondary">{{ __('No invoices yet.') }}</td></tr>
                        @endforelse
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
