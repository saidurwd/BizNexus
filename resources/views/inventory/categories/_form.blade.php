@php
    $accountRoles = [
        'inventory_account_id' => [__('Inventory account'), 'ASSET'],
        'cogs_account_id' => [__('Cost of goods sold account'), 'EXPENSE'],
        'revenue_account_id' => [__('Revenue account'), 'REVENUE'],
        'expense_account_id' => [__('Expense account (non-stock purchases)'), 'EXPENSE'],
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
        <label class="form-label">{{ __('Status') }}</label>
        <select name="status" class="form-select">
            <option value="active" @selected(($category?->status ?? 'active') === 'active')>{{ __('Active') }}</option>
            <option value="inactive" @selected($category?->status === 'inactive')>{{ __('Inactive') }}</option>
        </select>
    </div>
    <div class="w-100"></div>
    @foreach ($accountRoles as $column => [$label, $accountType])
        <div class="col-md-3">
            <label class="form-label">{{ $label }}</label>
            <select name="{{ $column }}" class="form-select">
                <option value="">—</option>
                @foreach ($accounts[$accountType] ?? [] as $account)
                    <option value="{{ $account->id }}" @selected((string) $category?->{$column} === (string) $account->id)>{{ $account->account_code }} — {{ $account->account_name }}</option>
                @endforeach
            </select>
        </div>
    @endforeach
    <div class="col-12"><button type="submit" class="btn btn-primary">{{ $submitLabel }}</button></div>
</form>
