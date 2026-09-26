@extends('layouts.erp')

@php
    $currencyCode = $order->currency?->code;
    $today = app(\Modules\Core\Services\CompanyContextService::class)->today()->toDateString();
@endphp

@section('title', __('Supplier invoice for :order', ['order' => $order->order_number]))

@section('content_header')
    <h1>{{ __('Supplier invoice for :order', ['order' => $order->order_number]) }}</h1>
    <p class="text-body-secondary mb-0">{{ __('Bill what has been received. The invoice may not bill more than was received, and its prices must stay within :tolerance% of the order.', ['tolerance' => Formatter::percent(config('inventory.price_tolerance_percent'))]) }}</p>
@endsection

@section('content')
    <form method="POST" action="{{ route('inventory.purchase-orders.invoice.store', $order->id) }}" class="card">
        @csrf
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Supplier') }}</label>
                    <input type="text" class="form-control" value="{{ $order->supplier?->name }}" disabled>
                </div>
                <div class="col-md-3">
                    <label for="invoice_number" class="form-label">{{ __("Supplier's invoice number") }}</label>
                    <input type="text" id="invoice_number" name="invoice_number" value="{{ old('invoice_number') }}" class="form-control @error('invoice_number') is-invalid @enderror" maxlength="50" required>
                    @error('invoice_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
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
                            <th class="text-end">{{ __('Received, not invoiced') }}</th>
                            <th style="width: 140px" class="text-end">{{ __('Qty invoiced') }}</th>
                            <th class="text-end">{{ __('Order price') }}</th>
                            <th style="width: 150px" class="text-end">{{ __('Invoice price') }}</th>
                            <th style="min-width: 150px">{{ __('Tax code') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->lines as $line)
                            @continue(bccomp($line->uninvoicedQuantity(), '0', 4) <= 0)
                            <tr>
                                <td><strong>{{ $line->product?->sku }}</strong> {{ $line->description }}</td>
                                <td class="text-end">{{ Formatter::quantity($line->uninvoicedQuantity(), $line->product?->unit?->decimals ?? 0) }} {{ $line->product?->unit?->code }}</td>
                                <td><input type="number" name="lines[{{ $line->id }}][quantity]" value="{{ old("lines.{$line->id}.quantity", (float) $line->uninvoicedQuantity()) }}" class="form-control form-control-sm text-end" step="any" min="0" aria-label="{{ __('Qty invoiced') }}"></td>
                                <td class="text-end">{{ Formatter::unitPrice($line->unit_price, $currencyCode) }}</td>
                                <td><input type="number" name="lines[{{ $line->id }}][unit_price]" value="{{ old("lines.{$line->id}.unit_price", (float) $line->unit_price) }}" class="form-control form-control-sm text-end" step="any" min="0" aria-label="{{ __('Invoice price') }}"></td>
                                <td>
                                    <select name="lines[{{ $line->id }}][tax_id]" class="form-select form-select-sm" aria-label="{{ __('Tax code') }}">
                                        <option value="">{{ __('By tax rules') }}</option>
                                        @foreach ($taxes as $tax)
                                            <option value="{{ $tax->id }}" @selected((string) old("lines.{$line->id}.tax_id", $line->tax_id) === (string) $tax->id)>{{ $tax->tax_code }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <label for="description" class="form-label">{{ __('Description') }}</label>
            <input type="text" id="description" name="description" value="{{ old('description') }}" class="form-control" maxlength="1000">
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Record invoice') }}</button>
            <a href="{{ route('inventory.purchase-orders.show', $order->id) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
