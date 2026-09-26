@php
    $creditNote ??= null;
    $invoice ??= null;
    $lines = old('lines', $creditNote?->lines->map->only(['account_id', 'description', 'quantity', 'unit_price', 'discount_amount', 'tax_id'])->all()
        ?? $invoice?->lines->map->only(['account_id', 'description', 'quantity', 'unit_price', 'discount_amount', 'tax_id'])->all()
        ?? []);
    $supplierId = old('supplier_id', $creditNote?->supplier_id ?? $invoice?->supplier_id);
    $invoiceId = old('supplier_invoice_id', $creditNote?->supplier_invoice_id ?? $invoice?->id);
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <label for="supplier_id" class="form-label">{{ __('Supplier') }}</label>
        <select id="supplier_id" name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" data-searchable required>
            <option value="">{{ __('Select supplier') }}</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected((string) $supplierId === (string) $supplier->id)>{{ $supplier->supplier_code ? $supplier->supplier_code.' — ' : '' }}{{ $supplier->name }}</option>
            @endforeach
        </select>
        @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="supplier_invoice_id" class="form-label">{{ __('Invoice credited') }}</label>
        <select id="supplier_invoice_id" name="supplier_invoice_id" class="form-select @error('supplier_invoice_id') is-invalid @enderror" data-searchable>
            <option value="">{{ __('None (credit on account)') }}</option>
            @foreach ($invoices as $openInvoice)
                <option value="{{ $openInvoice->id }}" @selected((string) $invoiceId === (string) $openInvoice->id)>
                    {{ $openInvoice->invoice_number }} · {{ $openInvoice->invoice_date->toDateString() }} · {{ Formatter::amount($openInvoice->outstanding_amount, $openInvoice->currency?->code) }} {{ __('owed') }}
                </option>
            @endforeach
        </select>
        <div class="form-text">{{ __('Must belong to the supplier. The credit uses its currency and exchange rate.') }}</div>
        @error('supplier_invoice_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="credit_note_number" class="form-label">{{ __('Credit note number') }}</label>
        <input type="text" id="credit_note_number" name="credit_note_number" class="form-control @error('credit_note_number') is-invalid @enderror" value="{{ old('credit_note_number', $creditNote?->credit_note_number) }}" maxlength="50" placeholder="{{ __('Assigned automatically') }}">
        @error('credit_note_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="credit_note_date" class="form-label">{{ __('Date') }}</label>
        <input type="date" id="credit_note_date" name="credit_note_date" class="form-control @error('credit_note_date') is-invalid @enderror" value="{{ old('credit_note_date', $creditNote?->credit_note_date?->toDateString() ?? now()->toDateString()) }}" required>
        @error('credit_note_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="currency_id" class="form-label">{{ __('Currency') }}</label>
        <select id="currency_id" name="currency_id" class="form-select">
            <option value="">{{ __('Company currency') }}</option>
            @foreach ($currencies as $currency)
                <option value="{{ $currency->id }}" @selected((string) old('currency_id', $creditNote?->currency_id ?? $invoice?->currency_id) === (string) $currency->id)>{{ $currency->code }} — {{ $currency->name }}</option>
            @endforeach
        </select>
        <div class="form-text">{{ __('Ignored when an invoice is credited.') }}</div>
    </div>
    <div class="col-md-4">
        <label for="discount_amount" class="form-label">{{ __('Discount') }}</label>
        <input type="number" id="discount_amount" name="discount_amount" class="form-control" value="{{ old('discount_amount', $creditNote?->discount_amount) }}" step="any" min="0">
    </div>
    <div class="col-12">
        <label for="reason" class="form-label">{{ __('Reason') }}</label>
        <textarea id="reason" name="reason" class="form-control @error('reason') is-invalid @enderror" rows="2" maxlength="1000" required placeholder="{{ __('For example: goods returned, price correction') }}">{{ old('reason', $creditNote?->reason ?? ($invoice ? __('Credit for invoice :number', ['number' => $invoice->invoice_number]) : '')) }}</textarea>
        @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<h5 class="mt-4">{{ __('Lines credited') }}</h5>
<x-finance.document-lines :accounts="$accounts" :taxes="$taxes" :lines="$lines" />
