@php
    $tax ??= null;
    $selectBool = fn (string $field, bool $default) => (string) (int) old($field, $tax?->{$field} ?? $default);
@endphp

<div class="row">
    <div class="col-md-4">
        <div class="mb-3">
            <label for="tax_code">Tax code</label>
            <input type="text" class="form-control" id="tax_code" name="tax_code" value="{{ old('tax_code', $tax?->tax_code) }}" required>
            @error('tax_code')<div class="text-danger mt-1">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="tax_name">Tax name</label>
            <input type="text" class="form-control" id="tax_name" name="tax_name" value="{{ old('tax_name', $tax?->tax_name) }}" required>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="tax_type">Tax type</label>
            <select class="form-control" id="tax_type" name="tax_type" required>
                @foreach (['VAT' => 'VAT / GST', 'SALES_TAX' => 'Sales tax', 'EXCISE' => 'Excise', 'WITHHOLDING_TAX' => 'Withholding tax', 'INCOME_TAX' => 'Income tax', 'OTHER' => 'Other'] as $type => $label)
                    <option value="{{ $type }}" @selected(old('tax_type', $tax?->tax_type ?? 'VAT') === $type)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="row">
    @unless ($tax)
        <div class="col-md-3">
            <div class="mb-3">
                <label for="rate">Rate (%)</label>
                <input type="number" class="form-control" id="rate" name="rate" step="0.0001" min="0" max="100" value="{{ old('rate') }}" required>
                @error('rate')<div class="text-danger mt-1">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="mb-3">
                <label for="effective_from">Effective from</label>
                <input type="date" class="form-control" id="effective_from" name="effective_from" value="{{ old('effective_from') }}">
                <small class="form-text text-muted">Leave empty to apply to all dates.</small>
            </div>
        </div>
    @endunless
    <div class="col-md-3">
        <x-form.country-select :value="$tax?->country_code" label="Jurisdiction country" />
    </div>
    <div class="col-md-3">
        <div class="mb-3">
            <label for="region_code">Region / state code</label>
            <input type="text" class="form-control" id="region_code" name="region_code" maxlength="10" value="{{ old('region_code', $tax?->region_code) }}">
        </div>
    </div>
</div>

<div class="row">
    @foreach ([
        'is_inclusive' => ['Prices include this tax', false],
        'is_recoverable' => ['Recoverable as input tax', true],
        'is_group' => ['Tax group (components below)', false],
    ] as $field => [$label, $default])
        <div class="col-md-3">
            <div class="mb-3">
                <label for="{{ $field }}">{{ $label }}</label>
                <select class="form-control" id="{{ $field }}" name="{{ $field }}">
                    <option value="1" @selected($selectBool($field, $default) === '1')>Yes</option>
                    <option value="0" @selected($selectBool($field, $default) === '0')>No</option>
                </select>
            </div>
        </div>
    @endforeach
    <div class="col-md-3">
        <div class="mb-3">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-control">
                <option value="active" @selected(old('status', $tax?->status ?? 'active') === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $tax?->status) === 'inactive')>Inactive</option>
            </select>
        </div>
    </div>
</div>

<div class="row">
    @foreach (['input_account_id' => 'Input tax account (purchases)', 'output_account_id' => 'Output tax account (sales, reverse charge)'] as $field => $label)
        <div class="col-md-6">
            <div class="mb-3">
                <label for="{{ $field }}">{{ $label }}</label>
                <select class="form-control" id="{{ $field }}" name="{{ $field }}">
                    <option value="">—</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" @selected((int) old($field, $tax?->{$field}) === $account->id)>{{ $account->account_code }} — {{ $account->account_name }}</option>
                    @endforeach
                </select>
                @error($field)<div class="text-danger mt-1">{{ $message }}</div>@enderror
            </div>
        </div>
    @endforeach
</div>
