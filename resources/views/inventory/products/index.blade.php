@extends('layouts.erp')

@section('title', __('Products'))

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="m-0">{{ __('Products') }}</h1>
        <div class="d-flex gap-2">
            @can('finance.data-import.use')
                <a href="{{ route('finance.imports.index') }}" class="btn btn-outline-secondary"><i class="bi bi-upload"></i> {{ __('Import') }}</a>
            @endcan
            @can('inventory.products.manage')
                <a href="{{ route('inventory.products.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> {{ __('New product') }}</a>
            @endcan
        </div>
    </div>
@endsection

@section('content')
    <form method="GET" class="card mb-3">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-4">
                <label for="q" class="form-label">{{ __('Search') }}</label>
                <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="{{ __('SKU, name or barcode') }}">
            </div>
            <div class="col-md-2">
                <label for="type" class="form-label">{{ __('Type') }}</label>
                <select id="type" name="type" class="form-select">
                    <option value="">{{ __('All types') }}</option>
                    @foreach (\Modules\Inventory\Models\Product::typeLabels() as $type => $label)
                        <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="category" class="form-label">{{ __('Category') }}</label>
                <select id="category" name="category" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['category'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">{{ __('Status') }}</label>
                <select id="status" name="status" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>{{ __('Active') }}</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>{{ __('Inactive') }}</option>
                </select>
            </div>
            <div class="col-auto">
                <div class="form-check mb-2">
                    <input type="checkbox" id="reorder" name="reorder" value="1" class="form-check-input" @checked($filters['reorder'] ?? false)>
                    <label for="reorder" class="form-check-label">{{ __('At or below reorder level') }}</label>
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('SKU') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Category') }}</th>
                        <th class="text-end">{{ __('On hand') }}</th>
                        <th class="text-end">{{ __('Average cost') }}</th>
                        <th class="text-end">{{ __('Sales price') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td><code>{{ $product->sku }}</code></td>
                            <td><a href="{{ route('inventory.products.show', $product->id) }}">{{ $product->name }}</a></td>
                            <td>{{ $product->typeLabel() }}</td>
                            <td>{{ $product->category?->name ?? '—' }}</td>
                            <td class="text-end">
                                @if ($product->isStocked())
                                    {{ Formatter::quantity($product->stock_quantity, $product->unit?->decimals ?? 0) }} {{ $product->unit?->code }}
                                    @if ($product->isBelowReorderLevel())
                                        <i class="bi bi-exclamation-triangle-fill text-warning" title="{{ __('At or below reorder level') }}"></i>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end">{{ $product->isStocked() ? Formatter::unitPrice($product->averageCost()) : '—' }}</td>
                            <td class="text-end">{{ Formatter::unitPrice($product->sales_price) }}</td>
                            <td><x-status-badge :status="$product->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-body-secondary py-4">{{ __('No products found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($products->hasPages())
            <div class="card-footer">{{ $products->links() }}</div>
        @endif
    </div>
@endsection
