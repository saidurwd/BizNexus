@php
    $invoice ??= null;
    $lines = old('lines', $invoice?->lines->map->only(['account_id', 'description', 'quantity', 'unit_price', 'discount_amount', 'tax_id'])->all() ?? []);
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <label for="customer_id" class="form-label">{{ __('Customer') }}</label>
        <select id="customer_id" name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" data-searchable required>
            <option value="">{{ __('Select customer') }}</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" data-due-days="{{ $customer->paymentTerm?->due_days }}" data-due-basis="{{ $customer->paymentTerm?->due_basis }}" @selected((string) old('customer_id', $invoice?->customer_id) === (string) $customer->id)>{{ $customer->customer_code ? $customer->customer_code.' — ' : '' }}{{ $customer->name }}</option>
            @endforeach
        </select>
        @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="invoice_number" class="form-label">{{ __('Invoice number') }}</label>
        <input type="text" id="invoice_number" name="invoice_number" class="form-control @error('invoice_number') is-invalid @enderror" value="{{ old('invoice_number', $invoice?->invoice_number) }}" maxlength="50" placeholder="{{ __('Assigned automatically') }}">
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
        <input type="date" id="due_date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date', $invoice?->due_date?->toDateString()) }}" data-due-date>
        <div class="form-text">{{ __("Filled in from the payment term; leave empty to use it.") }}</div>
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

@once
    @push('js')
        <script type="module">
            const party = document.querySelector('#customer_id');
            const invoiceDate = document.querySelector('#invoice_date');
            const dueDate = document.querySelector('[data-due-date]');

            const fillDueDate = () => {
                const option = party?.selectedOptions[0];
                if (!option || option.dataset.dueDays === '' || !invoiceDate.value) {
                    return;
                }
                const date = new Date(invoiceDate.value + 'T00:00:00Z');
                if (option.dataset.dueBasis === 'end_of_month') {
                    date.setUTCMonth(date.getUTCMonth() + 1, 0);
                }
                date.setUTCDate(date.getUTCDate() + Number(option.dataset.dueDays));
                dueDate.value = date.toISOString().slice(0, 10);
            };

            party?.addEventListener('change', fillDueDate);
            invoiceDate?.addEventListener('change', fillDueDate);
        </script>
    @endpush
@endonce
