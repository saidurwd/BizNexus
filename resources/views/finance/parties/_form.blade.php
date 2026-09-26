{{--
    Customer or supplier master data. Expects $party (or null), $isCustomer, $currencies, $paymentTerms and
    $controlAccounts.
--}}
@php
    $codeColumn = $isCustomer ? 'customer_code' : 'supplier_code';
    $controlColumn = $isCustomer ? 'receivable_account_id' : 'payable_account_id';
    $value = fn (string $field, $default = null) => old($field, $party?->{$field} ?? $default);
    $field = fn (string $name) => $errors->has($name) ? 'is-invalid' : '';
@endphp

<div class="row g-3">
    <div class="col-md-3">
        <label for="{{ $codeColumn }}" class="form-label">{{ __('Code') }}</label>
        <input type="text" id="{{ $codeColumn }}" name="{{ $codeColumn }}" value="{{ $value($codeColumn) }}" class="form-control {{ $field($codeColumn) }}" maxlength="50" required>
        @error($codeColumn)<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="name" class="form-label">{{ __('Name') }}</label>
        <input type="text" id="name" name="name" value="{{ $value('name') }}" class="form-control {{ $field('name') }}" maxlength="255" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label for="status" class="form-label">{{ __('Status') }}</label>
        <select id="status" name="status" class="form-select">
            <option value="active" @selected($value('status', 'active') === 'active')>{{ __('Active') }}</option>
            <option value="inactive" @selected($value('status') === 'inactive')>{{ __('Inactive') }}</option>
        </select>
    </div>

    <div class="col-md-4">
        <label for="contact_person" class="form-label">{{ __('Contact person') }}</label>
        <input type="text" id="contact_person" name="contact_person" value="{{ $value('contact_person') }}" class="form-control" maxlength="255">
    </div>
    <div class="col-md-4">
        <label for="email" class="form-label">{{ __('Email') }}</label>
        <input type="email" id="email" name="email" value="{{ $value('email') }}" class="form-control {{ $field('email') }}" maxlength="255">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="phone" class="form-label">{{ __('Phone') }}</label>
        <input type="text" id="phone" name="phone" value="{{ $value('phone') }}" class="form-control" maxlength="50">
    </div>

    <div class="col-md-4">
        <label for="tax_number" class="form-label">{{ __('Tax number') }}</label>
        <input type="text" id="tax_number" name="tax_number" value="{{ $value('tax_number') }}" class="form-control" maxlength="100">
        <div class="form-text">{{ __('VAT, GST or TIN. Used by the tax rules and printed on invoices.') }}</div>
    </div>
    <div class="col-md-4">
        <label for="country_code" class="form-label">{{ __('Country') }}</label>
        <select id="country_code" name="country_code" class="form-select {{ $field('country_code') }}" data-searchable>
            <option value="">—</option>
            @foreach (\Modules\Core\Support\Countries::options() as $code => $countryName)
                <option value="{{ $code }}" @selected($value('country_code') === $code)>{{ $countryName }} ({{ $code }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label for="currency_id" class="form-label">{{ __('Currency') }}</label>
        <select id="currency_id" name="currency_id" class="form-select">
            <option value="">{{ __('Company currency') }}</option>
            @foreach ($currencies as $currency)
                <option value="{{ $currency->id }}" @selected((string) $value('currency_id') === (string) $currency->id)>{{ $currency->code }} — {{ $currency->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12">
        <label for="address" class="form-label">{{ __('Address') }}</label>
        <textarea id="address" name="address" class="form-control" rows="2" maxlength="1000">{{ $value('address') }}</textarea>
    </div>

    <div class="col-md-4">
        <label for="payment_term_id" class="form-label">{{ __('Payment term') }}</label>
        <select id="payment_term_id" name="payment_term_id" class="form-select">
            <option value="">{{ __('Due on the invoice date') }}</option>
            @foreach ($paymentTerms as $term)
                <option value="{{ $term->id }}" @selected((string) $value('payment_term_id') === (string) $term->id)>{{ $term->code }} — {{ $term->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label for="{{ $controlColumn }}" class="form-label">{{ $isCustomer ? __('Receivable account') : __('Payable account') }}</label>
        <select id="{{ $controlColumn }}" name="{{ $controlColumn }}" class="form-select" data-searchable>
            <option value="">{{ __('Company default') }}</option>
            @foreach ($controlAccounts as $account)
                <option value="{{ $account->id }}" @selected((string) $value($controlColumn) === (string) $account->id)>{{ $account->account_code }} — {{ $account->account_name }}{{ $account->is_control_account ? ' ('.__('control').')' : '' }}</option>
            @endforeach
        </select>
    </div>
    @if ($isCustomer)
        <div class="col-md-4">
            <label for="credit_limit" class="form-label">{{ __('Credit limit') }}</label>
            <input type="number" id="credit_limit" name="credit_limit" value="{{ $value('credit_limit') }}" class="form-control {{ $field('credit_limit') }}" step="any" min="0">
            <div class="form-text">{{ __('In the company currency. Leave empty for no limit.') }}</div>
        </div>
    @endif
</div>
