{{--
    Asset details. Expects $asset (or null), $categories, $branches, $departments, $suppliers and $offsetAccounts.
    Cost and the depreciation policy are fixed once depreciation has been charged.
--}}
@php
    $value = fn (string $field, $default = null) => old($field, $asset?->{$field} instanceof \Carbon\CarbonInterface ? $asset->{$field}->toDateString() : ($asset?->{$field} ?? $default));
    $field = fn (string $name) => $errors->has($name) ? 'is-invalid' : '';
    $locked = $asset && $asset->transactions()->whereIn('type', ['depreciation', 'impairment', 'disposal'])->exists();
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">{{ __('Name') }}</label>
        <input type="text" id="name" name="name" value="{{ $value('name') }}" class="form-control {{ $field('name') }}" maxlength="255" required>
    </div>
    <div class="col-md-3">
        <label for="category_id" class="form-label">{{ __('Category') }}</label>
        <select id="category_id" name="category_id" class="form-select {{ $field('category_id') }}" @disabled($asset) required>
            <option value="">{{ __('Select category') }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) $value('category_id') === (string) $category->id)>{{ $category->code }} — {{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label for="supplier_id" class="form-label">{{ __('Supplier') }}</label>
        <select id="supplier_id" name="supplier_id" class="form-select" data-searchable>
            <option value="">—</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected((string) $value('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3">
        <label for="serial_number" class="form-label">{{ __('Serial number') }}</label>
        <input type="text" id="serial_number" name="serial_number" value="{{ $value('serial_number') }}" class="form-control" maxlength="100">
    </div>
    <div class="col-md-3">
        <label for="tag" class="form-label">{{ __('Asset tag') }}</label>
        <input type="text" id="tag" name="tag" value="{{ $value('tag') }}" class="form-control" maxlength="100">
    </div>
    <div class="col-md-3">
        <label for="branch_id" class="form-label">{{ __('Branch') }}</label>
        <select id="branch_id" name="branch_id" class="form-select">
            <option value="">—</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) $value('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label for="department_id" class="form-label">{{ __('Department') }}</label>
        <select id="department_id" name="department_id" class="form-select">
            <option value="">—</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected((string) $value('department_id') === (string) $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label for="location" class="form-label">{{ __('Location') }}</label>
        <input type="text" id="location" name="location" value="{{ $value('location') }}" class="form-control" maxlength="255">
    </div>
    <div class="col-md-6">
        <label for="custodian" class="form-label">{{ __('Custodian') }}</label>
        <input type="text" id="custodian" name="custodian" value="{{ $value('custodian') }}" class="form-control" maxlength="255" placeholder="{{ __('Who is responsible for it') }}">
    </div>
    <div class="col-12">
        <label for="description" class="form-label">{{ __('Description') }}</label>
        <textarea id="description" name="description" class="form-control" rows="2" maxlength="2000">{{ $value('description') }}</textarea>
    </div>

    <div class="col-12"><h5 class="mb-0 mt-2">{{ __('Cost and depreciation') }}</h5>
        @if ($locked)<div class="form-text">{{ __('Depreciation has been charged, so cost and the depreciation policy can no longer change. Use an impairment to write the asset down.') }}</div>@endif
    </div>
    <div class="col-md-3">
        <label for="acquisition_date" class="form-label">{{ __('Acquisition date') }}</label>
        <input type="date" id="acquisition_date" name="acquisition_date" value="{{ $value('acquisition_date', app(\Modules\Core\Services\CompanyContextService::class)->today()->toDateString()) }}" class="form-control {{ $field('acquisition_date') }}" @disabled($asset) required>
    </div>
    <div class="col-md-3">
        <label for="in_service_date" class="form-label">{{ __('In service from') }}</label>
        <input type="date" id="in_service_date" name="in_service_date" value="{{ $value('in_service_date') }}" class="form-control {{ $field('in_service_date') }}" @disabled($locked)>
        <div class="form-text">{{ __('Depreciation starts in this month. Defaults to the acquisition date.') }}</div>
    </div>
    <div class="col-md-3">
        <label for="cost" class="form-label">{{ __('Cost') }}</label>
        <input type="number" id="cost" name="cost" value="{{ $value('cost') }}" class="form-control {{ $field('cost') }}" step="any" min="0" @disabled($asset) required>
    </div>
    <div class="col-md-3">
        <label for="residual_value" class="form-label">{{ __('Residual value') }}</label>
        <input type="number" id="residual_value" name="residual_value" value="{{ $value('residual_value', 0) }}" class="form-control" step="any" min="0" @disabled($locked)>
    </div>
    <div class="col-md-3">
        <label for="depreciation_method" class="form-label">{{ __('Method') }}</label>
        <select id="depreciation_method" name="depreciation_method" class="form-select" @disabled($locked)>
            <option value="">{{ __('From category') }}</option>
            @foreach (\Modules\Assets\Models\AssetCategory::methodLabels() as $method => $label)
                <option value="{{ $method }}" @selected($value('depreciation_method') === $method)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label for="useful_life_months" class="form-label">{{ __('Useful life (months)') }}</label>
        <input type="number" id="useful_life_months" name="useful_life_months" value="{{ $value('useful_life_months') }}" class="form-control" min="1" max="1200" placeholder="{{ __('From category') }}" @disabled($locked)>
    </div>
    <div class="col-md-3">
        <label for="declining_rate" class="form-label">{{ __('Declining rate % a year') }}</label>
        <input type="number" id="declining_rate" name="declining_rate" value="{{ $value('declining_rate') ? (float) $value('declining_rate') : '' }}" class="form-control" step="any" min="0" max="100" placeholder="{{ __('From category') }}" @disabled($locked)>
    </div>

    @unless ($asset)
        <div class="col-12">
            <h5 class="mb-0 mt-2">{{ __('Posting') }}</h5>
            <div class="form-text">{{ __('For an asset bought through purchasing, use Capitalise purchases instead: the purchase has already been posted.') }}</div>
        </div>
        <div class="col-md-6">
            <label for="offset_account_id" class="form-label">{{ __('Post the cost against') }}</label>
            <select id="offset_account_id" name="offset_account_id" class="form-select" data-searchable>
                <option value="">{{ __('Nothing: already in the ledger (opening balance)') }}</option>
                @foreach ($offsetAccounts as $account)
                    <option value="{{ $account->id }}" @selected((string) old('offset_account_id') === (string) $account->id)>{{ $account->account_code }} — {{ $account->account_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="opening_accumulated_depreciation" class="form-label">{{ __('Accumulated depreciation to date') }}</label>
            <input type="number" id="opening_accumulated_depreciation" name="opening_accumulated_depreciation" value="{{ old('opening_accumulated_depreciation') }}" class="form-control" step="any" min="0">
        </div>
        <div class="col-md-3">
            <label for="depreciated_until" class="form-label">{{ __('Charged up to (month)') }}</label>
            <input type="month" id="depreciated_until" name="depreciated_until" value="{{ old('depreciated_until') }}" class="form-control">
            <div class="form-text">{{ __('For assets taken over with depreciation already charged.') }}</div>
        </div>
    @endunless
</div>
