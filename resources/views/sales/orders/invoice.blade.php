@extends('layouts.erp')

@php
    $currencyCode = $order->currencyCode();
    $today = app(\Modules\Core\Services\CompanyContextService::class)->today()->toDateString();
@endphp

@section('title', __('Invoice for :order', ['order' => $order->order_number]))

@section('content_header')
    <h1>{{ __('Invoice for :order', ['order' => $order->order_number]) }}</h1>
    <p class="text-body-secondary mb-0">{{ __('Invoice what has been delivered (services: what was ordered). Prices, discounts and tax codes come from the order.') }}</p>
@endsection

@section('content')
    <form method="POST" action="{{ route('sales.orders.invoice.store', $order->id) }}" class="card">
        @csrf
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Customer') }}</label>
                    <input type="text" class="form-control" value="{{ $order->customer?->name }}" disabled>
                </div>
                <div class="col-md-3">
                    <label for="invoice_date" class="form-label">{{ __('Invoice date') }}</label>
                    <input type="date" id="invoice_date" name="invoice_date" value="{{ old('invoice_date', $today) }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label for="due_date" class="form-label">{{ __('Due date') }}</label>
                    <input type="date" id="due_date" name="due_date" value="{{ old('due_date') }}" class="form-control @error('due_date') is-invalid @enderror">
                    <div class="form-text">{{ __('Filled from the payment term when left empty.') }}</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th class="text-end">{{ __('Ready to invoice') }}</th>
                            <th style="width: 150px" class="text-end">{{ __('Qty to invoice') }}</th>
                            <th class="text-end">{{ __('Unit price') }}</th>
                            <th>{{ __('Tax code') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->lines as $line)
                            @continue(bccomp($line->billableQuantity(), '0', 4) <= 0)
                            <tr>
                                <td><strong>{{ $line->product?->sku }}</strong> {{ $line->description }}</td>
                                <td class="text-end">{{ Formatter::quantity($line->billableQuantity(), $line->product?->unit?->decimals ?? 0) }} {{ $line->product?->unit?->code }}</td>
                                <td><input type="number" name="lines[{{ $line->id }}]" value="{{ old("lines.{$line->id}", (float) $line->billableQuantity()) }}" class="form-control form-control-sm text-end" step="any" min="0" max="{{ (float) $line->billableQuantity() }}" aria-label="{{ __('Qty to invoice') }}"></td>
                                <td class="text-end">{{ Formatter::unitPrice($line->unit_price, $currencyCode) }}</td>
                                <td>{{ $line->tax?->tax_code ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <label for="description" class="form-label">{{ __('Description') }}</label>
            <input type="text" id="description" name="description" value="{{ old('description') }}" class="form-control" maxlength="1000">
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Create invoice') }}</button>
            <a href="{{ route('sales.orders.show', $order->id) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
