{{--
    Product master data. Expects $product (or null), $categories, $units, $taxes, $suppliers and $accounts (grouped by type).
--}}
@php
    $value = fn (string $field, $default = null) => old($field, $product?->{$field} ?? $default);
    $field = fn (string $name) => $errors->has($name) ? 'is-invalid' : '';
    $accountRoles = [
        'inventory_account_id' => [__('Inventory account'), 'ASSET'],
        'cogs_account_id' => [__('Cost of goods sold account'), 'EXPENSE'],
        'revenue_account_id' => [__('Revenue account'), 'REVENUE'],
        'expense_account_id' => [__('Expense account (non-stock purchases)'), 'EXPENSE'],
    ];
@endphp

<div class="row g-3">
    <div class="col-md-3">
        <label for="sku" class="form-label">{{ __('SKU') }}</label>
        <input type="text" id="sku" name="sku" value="{{ $value('sku') }}" class="form-control {{ $field('sku') }}" maxlength="50" required>
        @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

    <div class="col-md-3">
        <label for="type" class="form-label">{{ __('Type') }}</label>
        <select id="type" name="type" class="form-select {{ $field('type') }}">
            @foreach (\Modules\Inventory\Models\Product::typeLabels() as $type => $label)
                <option value="{{ $type }}" @selected($value('type', 'stock') === $type)>{{ $label }}</option>
            @endforeach
        </select>
        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">{{ __('Stock items are counted in warehouses and valued at weighted average cost.') }}</div>
    </div>
    <div class="col-md-3">
        <label for="category_id" class="form-label">{{ __('Category') }}</label>
        <select id="category_id" name="category_id" class="form-select {{ $field('category_id') }}">
            <option value="">—</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) $value('category_id') === (string) $category->id)>{{ $category->code }} — {{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label for="unit_id" class="form-label">{{ __('Unit of measure') }}</label>
        <select id="unit_id" name="unit_id" class="form-select {{ $field('unit_id') }}" required>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" @selected((string) $value('unit_id') === (string) $unit->id)>{{ $unit->code }} — {{ $unit->name }}</option>
            @endforeach
        </select>
        @error('unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label for="barcode" class="form-label">{{ __('Barcode') }}</label>
        <input type="text" id="barcode" name="barcode" value="{{ $value('barcode') }}" class="form-control" maxlength="50">
    </div>

    <div class="col-12">
        <label for="description" class="form-label">{{ __('Description') }}</label>
        <textarea id="description" name="description" class="form-control" rows="2" maxlength="2000">{{ $value('description') }}</textarea>
    </div>

    <div class="col-12"><h5 class="mb-0 mt-2">{{ __('Buying and selling') }}</h5></div>
    <div class="col-md-3">
        <label for="purchase_price" class="form-label">{{ __('Purchase price') }}</label>
        <input type="number" id="purchase_price" name="purchase_price" value="{{ $value('purchase_price') }}" class="form-control {{ $field('purchase_price') }}" step="any" min="0">
        @error('purchase_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label for="purchase_tax_id" class="form-label">{{ __('Purchase tax') }}</label>
        <select id="purchase_tax_id" name="purchase_tax_id" class="form-select">
            <option value="">{{ __('By tax rules') }}</option>
            @foreach ($taxes as $tax)
                <option value="{{ $tax->id }}" @selected((string) $value('purchase_tax_id') === (string) $tax->id)>{{ $tax->tax_code }} — {{ $tax->tax_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label for="sales_price" class="form-label">{{ __('Sales price') }}</label>
        <input type="number" id="sales_price" name="sales_price" value="{{ $value('sales_price') }}" class="form-control {{ $field('sales_price') }}" step="any" min="0">
        @error('sales_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label for="sales_tax_id" class="form-label">{{ __('Sales tax') }}</label>
        <select id="sales_tax_id" name="sales_tax_id" class="form-select">
            <option value="">{{ __('By tax rules') }}</option>
            @foreach ($taxes as $tax)
                <option value="{{ $tax->id }}" @selected((string) $value('sales_tax_id') === (string) $tax->id)>{{ $tax->tax_code }} — {{ $tax->tax_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label for="preferred_supplier_id" class="form-label">{{ __('Preferred supplier') }}</label>
        <select id="preferred_supplier_id" name="preferred_supplier_id" class="form-select" data-searchable>
            <option value="">—</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected((string) $value('preferred_supplier_id') === (string) $supplier->id)>{{ $supplier->supplier_code }} — {{ $supplier->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label for="reorder_level" class="form-label">{{ __('Reorder level') }}</label>
        <input type="number" id="reorder_level" name="reorder_level" value="{{ $value('reorder_level') }}" class="form-control {{ $field('reorder_level') }}" step="any" min="0">
        <div class="form-text">{{ __('Flag the product when stock falls to this quantity.') }}</div>
    </div>
    <div class="col-md-4">
        <label for="reorder_quantity" class="form-label">{{ __('Reorder quantity') }}</label>
        <input type="number" id="reorder_quantity" name="reorder_quantity" value="{{ $value('reorder_quantity') }}" class="form-control {{ $field('reorder_quantity') }}" step="any" min="0">
        @error('reorder_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <h5 class="mb-0 mt-2">{{ __('Ledger accounts') }}</h5>
        <div class="form-text">{{ __('Leave empty to use the category\'s accounts.') }}</div>
    </div>
    @foreach ($accountRoles as $column => [$label, $accountType])
        <div class="col-md-6">
            <label for="{{ $column }}" class="form-label">{{ $label }}</label>
            <select id="{{ $column }}" name="{{ $column }}" class="form-select {{ $field($column) }}" data-searchable>
                <option value="">{{ __('From category') }}</option>
                @foreach ($accounts[$accountType] ?? [] as $account)
                    <option value="{{ $account->id }}" @selected((string) $value($column) === (string) $account->id)>{{ $account->account_code }} — {{ $account->account_name }}</option>
                @endforeach
            </select>
            @error($column)<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @endforeach
</div>
