@php
    $creditNote ??= null;
    $invoice ??= null;
    $lines = old('lines', $creditNote?->lines->map->only(['product_id', 'warehouse_id', 'account_id', 'description', 'quantity', 'unit_price', 'discount_amount', 'tax_id'])->all()
        ?? $invoice?->lines->map->only(['product_id', 'account_id', 'description', 'quantity', 'unit_price', 'discount_amount', 'tax_id'])->all()
        ?? []);
    $customerId = old('customer_id', $creditNote?->customer_id ?? $invoice?->customer_id);
    $invoiceId = old('customer_invoice_id', $creditNote?->customer_invoice_id ?? $invoice?->id);
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <label for="customer_id" class="form-label">{{ __('Customer') }}</label>
        <select id="customer_id" name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" data-searchable required>
            <option value="">{{ __('Select customer') }}</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected((string) $customerId === (string) $customer->id)>{{ $customer->customer_code ? $customer->customer_code.' — ' : '' }}{{ $customer->name }}</option>
            @endforeach
        </select>
        @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="customer_invoice_id" class="form-label">{{ __('Invoice credited') }}</label>
        <select id="customer_invoice_id" name="customer_invoice_id" class="form-select @error('customer_invoice_id') is-invalid @enderror" data-searchable>
            <option value="">{{ __('None (credit on account)') }}</option>
            @foreach ($invoices as $openInvoice)
                <option value="{{ $openInvoice->id }}" @selected((string) $invoiceId === (string) $openInvoice->id)>
                    {{ $openInvoice->invoice_number }} · {{ $openInvoice->invoice_date->toDateString() }} · {{ Formatter::amount($openInvoice->outstanding_amount, $openInvoice->currency?->code) }} {{ __('owed') }}
                </option>
            @endforeach
        </select>
        <div class="form-text">{{ __('Must belong to the customer. The credit uses its currency and exchange rate.') }}</div>
        @error('customer_invoice_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="note_number" class="form-label">{{ __('Credit note number') }}</label>
        <input type="text" id="note_number" name="note_number" class="form-control @error('note_number') is-invalid @enderror" value="{{ old('note_number', $creditNote?->note_number) }}" maxlength="50" placeholder="{{ __('Assigned automatically') }}">
        @error('note_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="note_date" class="form-label">{{ __('Date') }}</label>
        <input type="date" id="note_date" name="note_date" class="form-control @error('note_date') is-invalid @enderror" value="{{ old('note_date', $creditNote?->note_date?->toDateString() ?? now()->toDateString()) }}" required>
        @error('note_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
        <label for="description" class="form-label">{{ __('Reason') }}</label>
        <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="2" maxlength="1000" required placeholder="{{ __('For example: goods returned, price correction') }}">{{ old('description', $creditNote?->description ?? ($invoice ? __('Credit for invoice :number', ['number' => $invoice->invoice_number]) : '')) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<h5 class="mt-4">{{ __('Lines credited') }}</h5>
<x-finance.document-lines :accounts="$accounts" :taxes="$taxes" :lines="$lines" :products="$products ?? null" :warehouses="$warehouses ?? null" />
<div class="form-text">{{ __('To take returned goods back into stock, choose the warehouse on the product line. Lines without a warehouse only credit the amount.') }}</div>
