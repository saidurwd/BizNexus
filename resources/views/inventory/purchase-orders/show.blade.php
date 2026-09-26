@extends('layouts.erp')

@php
    $currencyCode = $order->currency?->code;
    $plain = fn ($quantity, $line) => Formatter::quantity($quantity, $line->product?->unit?->decimals ?? 0);
@endphp

@section('title', __('Purchase order :number', ['number' => $order->order_number]))

@section('content_header')
    <div class="d-flex flex-wrap align-items-center gap-2">
        <h1 class="m-0">{{ __('Purchase order :number', ['number' => $order->order_number]) }}</h1>
        <x-status-badge :status="$order->status" class="fs-6" />
    </div>
@endsection

@section('content')
    @if ($order->hasCreditDue())
        <div class="alert alert-warning">{{ __('Goods returned after they were invoiced are waiting for the supplier\'s credit note.') }}</div>
    @endif
    @if ($order->status === 'REJECTED' && $order->rejection_reason)
        <div class="alert alert-warning">{{ __('Rejected: :reason', ['reason' => $order->rejection_reason]) }}</div>
    @endif

    <div class="row">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">{{ __('Supplier') }}</dt>
                        <dd class="col-sm-8"><a href="{{ route('finance.suppliers.show', $order->supplier_id) }}">{{ $order->supplier?->name }}</a></dd>
                        <dt class="col-sm-4">{{ __('Supplier reference') }}</dt>
                        <dd class="col-sm-8">{{ $order->supplier_reference ?? '—' }}</dd>
                        <dt class="col-sm-4">{{ __('Order date') }}</dt>
                        <dd class="col-sm-8">{{ Formatter::date($order->order_date) }}</dd>
                        <dt class="col-sm-4">{{ __('Expected delivery') }}</dt>
                        <dd class="col-sm-8">{{ $order->expected_date ? Formatter::date($order->expected_date) : '—' }}</dd>
                        <dt class="col-sm-4">{{ __('Deliver to') }}</dt>
                        <dd class="col-sm-8">{{ $order->warehouse?->code }} — {{ $order->warehouse?->name }}</dd>
                        <dt class="col-sm-4">{{ __('Currency') }}</dt>
                        <dd class="col-sm-8">{{ $currencyCode ?? __('Company currency') }}</dd>
                        <dt class="col-sm-4">{{ __('Created by') }}</dt>
                        <dd class="col-sm-8">{{ $order->createdBy?->name ?? '—' }}</dd>
                        @if ($order->approvedBy)
                            <dt class="col-sm-4">{{ __('Approved by') }}</dt>
                            <dd class="col-sm-8">{{ $order->approvedBy->name }} · {{ Formatter::date($order->approved_at) }}</dd>
                        @endif
                        @if ($order->notes)
                            <dt class="col-sm-4">{{ __('Notes') }}</dt>
                            <dd class="col-sm-8">{!! nl2br(e($order->notes)) !!}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card mb-3">
                <table class="table table-sm mb-0">
                    <tr><th>{{ __('Net') }}</th><td class="text-end">{{ Formatter::amount($order->subtotal, $currencyCode) }}</td></tr>
                    <tr><th>{{ __('Estimated tax') }}</th><td class="text-end">{{ Formatter::amount($order->tax_amount, $currencyCode) }}</td></tr>
                    <tr class="fw-bold"><th>{{ __('Total') }}</th><td class="text-end">{{ Formatter::amount($order->total_amount, $currencyCode) }}</td></tr>
                </table>
                <div class="card-footer small text-body-secondary">{{ __('The supplier invoice carries the tax that is posted.') }}</div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body table-responsive p-0">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Product') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th class="text-end">{{ __('Ordered') }}</th>
                        <th class="text-end">{{ __('Received') }}</th>
                        <th class="text-end">{{ __('Invoiced') }}</th>
                        <th class="text-end">{{ __('Unit price') }}</th>
                        <th>{{ __('Tax code') }}</th>
                        <th class="text-end">{{ __('Net') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->lines as $line)
                        <tr>
                            <td><a href="{{ route('inventory.products.show', $line->product_id) }}">{{ $line->product?->sku }}</a></td>
                            <td>{{ $line->description }}</td>
                            <td class="text-end">{{ $plain($line->quantity, $line) }} {{ $line->product?->unit?->code }}</td>
                            <td class="text-end">{{ $plain($line->received_quantity, $line) }}</td>
                            <td class="text-end">{{ $plain($line->invoiced_quantity, $line) }}</td>
                            <td class="text-end">{{ Formatter::unitPrice($line->unit_price, $currencyCode) }}</td>
                            <td>{{ $line->tax?->tax_code ?? '—' }}</td>
                            <td class="text-end">{{ Formatter::amount($line->subtotal, $currencyCode) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('inventory.purchase-orders.index') }}" class="btn btn-secondary">{{ __('Back') }}</a>

        @if ($order->isEditable())
            @can('inventory.purchase-orders.create')
                <a href="{{ route('inventory.purchase-orders.edit', $order->id) }}" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> {{ __('Edit') }}</a>
            @endcan
        @endif

        @if ($order->status === 'DRAFT')
            @can('inventory.purchase-orders.submit')
                <form method="POST" action="{{ route('inventory.purchase-orders.submit', $order->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-success"><i class="bi bi-send"></i> {{ __('Submit for approval') }}</button>
                </form>
            @endcan
            @can('inventory.purchase-orders.create')
                <form method="POST" action="{{ route('inventory.purchase-orders.destroy', $order->id) }}" onsubmit="return confirm(@js(__('Delete this purchase order?')))">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i> {{ __('Delete') }}</button>
                </form>
            @endcan
        @endif

        @if ($order->status === 'SUBMITTED')
            @can('inventory.purchase-orders.approve')
                <form method="POST" action="{{ route('inventory.purchase-orders.approve', $order->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-circle"></i> {{ __('Approve') }}</button>
                </form>
                <form method="POST" action="{{ route('inventory.purchase-orders.reject', $order->id) }}" class="d-flex gap-2">
                    @csrf
                    <input type="text" name="reason" class="form-control" placeholder="{{ __('Reason for rejecting') }}" maxlength="500">
                    <button type="submit" class="btn btn-outline-danger">{{ __('Reject') }}</button>
                </form>
            @endcan
        @endif

        @if ($order->canReceive())
            @can('inventory.goods-receipts.create')
                <a href="{{ route('inventory.goods-receipts.create', $order->id) }}" class="btn btn-primary"><i class="bi bi-box-arrow-in-down"></i> {{ __('Receive goods') }}</a>
            @endcan
        @endif

        @if ($order->canReturn())
            @can('inventory.supplier-returns.create')
                <a href="{{ route('inventory.supplier-returns.create', $order->id) }}" class="btn btn-outline-primary"><i class="bi bi-arrow-return-left"></i> {{ __('Return goods') }}</a>
            @endcan
        @endif

        @if ($order->hasCreditDue())
            @can('finance.supplier-credit-notes.create')
                <a href="{{ route('inventory.purchase-orders.credit-note.create', $order->id) }}" class="btn btn-warning"><i class="bi bi-file-earmark-minus"></i> {{ __('Record supplier credit note') }}</a>
            @endcan
        @endif

        @if ($order->hasUninvoicedReceipts())
            @can('finance.supplier-invoices.create')
                <a href="{{ route('inventory.purchase-orders.invoice.create', $order->id) }}" class="btn btn-primary"><i class="bi bi-receipt"></i> {{ __('Record supplier invoice') }}</a>
            @endcan
        @endif

        @can('inventory.purchase-orders.cancel')
            @if (in_array($order->status, ['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED'], true))
                <form method="POST" action="{{ route('inventory.purchase-orders.cancel', $order->id) }}" onsubmit="return confirm(@js(__('Cancel this purchase order?')))">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger">{{ __('Cancel order') }}</button>
                </form>
            @endif
            @if (in_array($order->status, ['PARTIALLY_RECEIVED', 'RECEIVED'], true) && ! $order->hasUninvoicedReceipts() && ! $order->hasCreditDue())
                <form method="POST" action="{{ route('inventory.purchase-orders.close', $order->id) }}" onsubmit="return confirm(@js(__('Close this order? Quantities not yet received will not be delivered.')))">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary">{{ __('Close order') }}</button>
                </form>
            @endif
        @endcan
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ __('Goods receipts') }}</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        @forelse ($order->receipts as $receipt)
                            <tr>
                                <td><a href="{{ route('inventory.goods-receipts.show', $receipt->id) }}">{{ $receipt->receipt_number }}</a></td>
                                <td>{{ Formatter::date($receipt->receipt_date) }}</td>
                                <td>{{ $receipt->delivery_note }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-body-secondary">{{ __('Nothing received yet.') }}</td></tr>
                        @endforelse
                        @foreach ($order->returns as $return)
                            <tr class="table-light">
                                <td><a href="{{ route('inventory.supplier-returns.show', $return->id) }}">{{ $return->return_number }}</a> <span class="badge text-bg-secondary">{{ __('Return') }}</span></td>
                                <td>{{ Formatter::date($return->return_date) }}</td>
                                <td>{{ $return->reason }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ __('Supplier invoices') }}</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        @forelse ($order->invoices as $invoice)
                            <tr>
                                <td><a href="{{ route('finance.supplier-invoices.show', $invoice->id) }}">{{ $invoice->invoice_number }}</a></td>
                                <td>{{ Formatter::date($invoice->invoice_date) }}</td>
                                <td class="text-end">{{ Formatter::amount($invoice->total_amount, $currencyCode) }}</td>
                                <td><x-status-badge :status="$invoice->status" /></td>
                            </tr>
                        @empty
                            <tr><td class="text-body-secondary">{{ __('No invoices yet.') }}</td></tr>
                        @endforelse
                        @foreach ($order->creditNotes as $creditNote)
                            <tr class="table-light">
                                <td><a href="{{ route('finance.supplier-credit-notes.show', $creditNote->id) }}">{{ $creditNote->credit_note_number }}</a> <span class="badge text-bg-secondary">{{ __('Credit note') }}</span></td>
                                <td>{{ Formatter::date($creditNote->credit_note_date) }}</td>
                                <td class="text-end">-{{ Formatter::amount($creditNote->total_amount, $currencyCode) }}</td>
                                <td><x-status-badge :status="$creditNote->status" /></td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
