{{-- Customer, reference and currency fields shared by quotations and sales orders. Expects $document, $customers and $currencies. --}}
@php
    $value = fn (string $field, $default = null) => old($field, $document?->{$field} ?? $default);
@endphp
<div class="col-md-5">
    <label for="customer_id" class="form-label">{{ __('Customer') }}</label>
    <select id="customer_id" name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" data-searchable required>
        <option value="">{{ __('Select customer') }}</option>
        @foreach ($customers as $customer)
            <option value="{{ $customer->id }}" @selected((string) $value('customer_id') === (string) $customer->id)>{{ $customer->customer_code ? $customer->customer_code.' — ' : '' }}{{ $customer->name }}</option>
        @endforeach
    </select>
    @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-4">
    <label for="customer_reference" class="form-label">{{ __('Customer reference') }}</label>
    <input type="text" id="customer_reference" name="customer_reference" value="{{ $value('customer_reference') }}" class="form-control" maxlength="100" placeholder="{{ __('e.g. their purchase order number') }}">
</div>
<div class="col-md-3">
    <label for="currency_id" class="form-label">{{ __('Currency') }}</label>
    <select id="currency_id" name="currency_id" class="form-select">
        <option value="">{{ __('Company currency') }}</option>
        @foreach ($currencies as $currency)
            <option value="{{ $currency->id }}" @selected((string) $value('currency_id') === (string) $currency->id)>{{ $currency->code }}</option>
        @endforeach
    </select>
</div>
