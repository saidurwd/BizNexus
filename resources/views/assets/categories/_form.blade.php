@php
    $accountFields = [
        'asset_account_id' => [__('Asset account (cost)'), ['ASSET']],
        'accumulated_depreciation_account_id' => [__('Accumulated depreciation account'), ['ASSET']],
        'depreciation_expense_account_id' => [__('Depreciation expense account'), ['EXPENSE']],
        'disposal_account_id' => [__('Gain or loss on disposal account'), ['EXPENSE', 'REVENUE']],
    ];
@endphp
<form method="POST" action="{{ $action }}" class="row g-2 align-items-end">
    @csrf
    @method($method)
    <div class="col-md-2">
        <label class="form-label">{{ __('Code') }}</label>
        <input type="text" name="code" value="{{ $category?->code }}" class="form-control" maxlength="20" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Name') }}</label>
        <input type="text" name="name" value="{{ $category?->name }}" class="form-control" maxlength="255" required>
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('Method') }}</label>
        <select name="depreciation_method" class="form-select">
            @foreach (\Modules\Assets\Models\AssetCategory::methodLabels() as $method => $label)
                <option value="{{ $method }}" @selected(($category?->depreciation_method ?? 'straight_line') === $method)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('Useful life (months)') }}</label>
        <input type="number" name="useful_life_months" value="{{ $category?->useful_life_months ?? 60 }}" class="form-control" min="1" max="1200" required>
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('Declining rate % a year') }}</label>
        <input type="number" name="declining_rate" value="{{ $category?->declining_rate ? (float) $category->declining_rate : '' }}" class="form-control" step="any" min="0" max="100">
    </div>
    @foreach ($accountFields as $field => [$label, $types])
        <div class="col-md-3">
            <label class="form-label">{{ $label }}</label>
            <select name="{{ $field }}" class="form-select" required>
                <option value="">{{ __('Select account') }}</option>
                @foreach ($types as $type)
                    @foreach ($accounts[$type] ?? [] as $account)
                        <option value="{{ $account->id }}" @selected((string) $category?->{$field} === (string) $account->id)>{{ $account->account_code }} — {{ $account->account_name }}</option>
                    @endforeach
                @endforeach
            </select>
        </div>
    @endforeach
    <div class="col-md-2">
        <label class="form-label">{{ __('Status') }}</label>
        <select name="status" class="form-select">
            <option value="active" @selected(($category?->status ?? 'active') === 'active')>{{ __('Active') }}</option>
            <option value="inactive" @selected($category?->status === 'inactive')>{{ __('Inactive') }}</option>
        </select>
    </div>
    <div class="col-auto"><button type="submit" class="btn btn-primary">{{ $submitLabel }}</button></div>
</form>
