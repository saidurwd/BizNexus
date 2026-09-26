@extends('layouts.erp')

@php
    $currencyCode = $order->currency?->code;
    $today = app(\Modules\Core\Services\CompanyContextService::class)->today()->toDateString();
@endphp

@section('title', __('Supplier credit note for :order', ['order' => $order->order_number]))

@section('content_header')
    <h1>{{ __('Supplier credit note for :order', ['order' => $order->order_number]) }}</h1>
    <p class="text-body-secondary mb-0">{{ __('Record the credit the supplier gives for goods returned after they were invoiced.') }}</p>
@endsection

@section('content')
    <form method="POST" action="{{ route('inventory.purchase-orders.credit-note.store', $order->id) }}" class="card">
        @csrf
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Supplier') }}</label>
                    <input type="text" class="form-control" value="{{ $order->supplier?->name }}" disabled>
                </div>
                <div class="col-md-3">
                    <label for="credit_note_number" class="form-label">{{ __("Supplier's credit note number") }}</label>
                    <input type="text" id="credit_note_number" name="credit_note_number" value="{{ old('credit_note_number') }}" class="form-control @error('credit_note_number') is-invalid @enderror" maxlength="50" required>
                    @error('credit_note_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label for="credit_note_date" class="form-label">{{ __('Date') }}</label>
                    <input type="date" id="credit_note_date" name="credit_note_date" value="{{ old('credit_note_date', $today) }}" class="form-control" required>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th class="text-end">{{ __('Credit due') }}</th>
                            <th style="width: 140px" class="text-end">{{ __('Qty credited') }}</th>
                            <th style="width: 150px" class="text-end">{{ __('Unit price') }}</th>
                            <th style="min-width: 150px">{{ __('Tax code') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->lines as $line)
                            @continue(bccomp($line->creditDueQuantity(), '0', 4) <= 0)
                            <tr>
                                <td><strong>{{ $line->product?->sku }}</strong> {{ $line->description }}</td>
                                <td class="text-end">{{ Formatter::quantity($line->creditDueQuantity(), $line->product?->unit?->decimals ?? 0) }} {{ $line->product?->unit?->code }}</td>
                                <td><input type="number" name="lines[{{ $line->id }}][quantity]" value="{{ old("lines.{$line->id}.quantity", (float) $line->creditDueQuantity()) }}" class="form-control form-control-sm text-end" step="any" min="0" aria-label="{{ __('Qty credited') }}"></td>
                                <td><input type="number" name="lines[{{ $line->id }}][unit_price]" value="{{ old("lines.{$line->id}.unit_price", (float) $line->unit_price) }}" class="form-control form-control-sm text-end" step="any" min="0" aria-label="{{ __('Unit price') }}"></td>
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

            <label for="reason" class="form-label">{{ __('Reason') }}</label>
            <input type="text" id="reason" name="reason" value="{{ old('reason') }}" class="form-control" maxlength="1000">
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Record credit note') }}</button>
            <a href="{{ route('inventory.purchase-orders.show', $order->id) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
