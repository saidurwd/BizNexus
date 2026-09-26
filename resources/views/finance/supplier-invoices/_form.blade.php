@php
    $invoice ??= null;
    $lines = old('lines', $invoice?->lines->map->only(['account_id', 'description', 'quantity', 'unit_price', 'discount_amount', 'tax_id'])->all() ?? []);
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <label for="supplier_id" class="form-label">{{ __('Supplier') }}</label>
        <select id="supplier_id" name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" data-searchable required>
            <option value="">{{ __('Select supplier') }}</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $invoice?->supplier_id) === (string) $supplier->id)>{{ $supplier->supplier_code ? $supplier->supplier_code.' — ' : '' }}{{ $supplier->name }}</option>
            @endforeach
        </select>
        @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="invoice_number" class="form-label">{{ __("Supplier's invoice number") }}</label>
        <input type="text" id="invoice_number" name="invoice_number" class="form-control @error('invoice_number') is-invalid @enderror" value="{{ old('invoice_number', $invoice?->invoice_number) }}" maxlength="50" required>
        @error('invoice_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="currency_id" class="form-label">{{ __('Currency') }}</label>
        <select id="currency_id" name="currency_id" class="form-select @error('currency_id') is-invalid @enderror">
            <option value="">{{ __('Company currency') }}</option>
            @foreach ($currencies as $currency)
                <option value="{{ $currency->id }}" @selected((string) old('currency_id', $invoice?->currency_id) === (string) $currency->id)>{{ $currency->code }} — {{ $currency->name }}</option>
            @endforeach
        </select>
        @error('currency_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="invoice_date" class="form-label">{{ __('Invoice date') }}</label>
        <input type="date" id="invoice_date" name="invoice_date" class="form-control @error('invoice_date') is-invalid @enderror" value="{{ old('invoice_date', $invoice?->invoice_date?->toDateString() ?? now()->toDateString()) }}" required>
        @error('invoice_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="due_date" class="form-label">{{ __('Due date') }}</label>
        <input type="date" id="due_date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date', $invoice?->due_date?->toDateString() ?? now()->addDays(30)->toDateString()) }}" required>
        @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="discount_amount" class="form-label">{{ __('Invoice discount') }}</label>
        <input type="number" id="discount_amount" name="discount_amount" class="form-control @error('discount_amount') is-invalid @enderror" value="{{ old('discount_amount', $invoice?->discount_amount) }}" step="any" min="0">
        <div class="form-text">{{ __('Spread over the lines before tax.') }}</div>
    </div>
    <div class="col-12">
        <label for="description" class="form-label">{{ __('Description') }}</label>
        <textarea id="description" name="description" class="form-control" rows="2" maxlength="1000">{{ old('description', $invoice?->description) }}</textarea>
    </div>
</div>

<h5 class="mt-4">{{ __('Lines') }}</h5>
<x-finance.document-lines :accounts="$accounts" :taxes="$taxes" :lines="$lines" />
