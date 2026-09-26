@extends('layouts.erp')

@section('title', __('Stock on Hand'))

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="m-0">{{ __('Stock on Hand') }}</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('inventory.stock.movements') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left-right"></i> {{ __('Stock movements') }}</a>
            <a href="{{ route('inventory.stock.valuation') }}" class="btn btn-outline-secondary"><i class="bi bi-cash-stack"></i> {{ __('Inventory valuation') }}</a>
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
                <label for="warehouse" class="form-label">{{ __('Warehouse') }}</label>
                <select id="warehouse" name="warehouse" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected((string) ($filters['warehouse'] ?? '') === (string) $warehouse->id)>{{ $warehouse->code }}</option>
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
            <div class="col-auto">
                <div class="form-check mb-2">
                    <input type="checkbox" id="reorder" name="reorder" value="1" class="form-check-input" @checked($filters['reorder'] ?? false)>
                    <label for="reorder" class="form-check-label">{{ __('At or below reorder level') }}</label>
                </div>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-primary">{{ __('Filter') }}</button></div>
        </div>
    </form>

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h3 class="card-title">{{ __('Quantities by warehouse') }}</h3>
            <span>{{ __('Total stock value') }}: <strong>{{ Formatter::amount($totalValue) }}</strong></span>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('SKU') }}</th>
                        <th>{{ __('Name') }}</th>
                        @foreach ($warehouses as $warehouse)
                            <th class="text-end">{{ $warehouse->code }}</th>
                        @endforeach
                        <th class="text-end">{{ __('Total') }}</th>
                        <th class="text-end">{{ __('Average cost') }}</th>
                        <th class="text-end">{{ __('Value') }}</th>
                        @can('inventory.purchase-orders.create')<th></th>@endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        @php $decimals = $product->unit?->decimals ?? 0; @endphp
                        <tr class="{{ $product->isBelowReorderLevel() ? 'table-warning' : '' }}">
                            <td><a href="{{ route('inventory.products.show', $product->id) }}"><code>{{ $product->sku }}</code></a></td>
                            <td>{{ $product->name }}</td>
                            @foreach ($warehouses as $warehouse)
                                @php $quantity = $balances[$product->id][$warehouse->id] ?? null; @endphp
                                <td class="text-end">{{ $quantity !== null && bccomp((string) $quantity, '0', 4) !== 0 ? Formatter::quantity($quantity, $decimals) : '—' }}</td>
                            @endforeach
                            <td class="text-end fw-bold">{{ Formatter::quantity($product->stock_quantity, $decimals) }} {{ $product->unit?->code }}</td>
                            <td class="text-end">{{ Formatter::unitPrice($product->averageCost()) }}</td>
                            <td class="text-end">{{ Formatter::amount($product->stock_value) }}</td>
                            @can('inventory.purchase-orders.create')
                                <td class="text-end">
                                    @if ($product->isBelowReorderLevel())
                                        <a href="{{ route('inventory.purchase-orders.create', ['product' => $product->id]) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Order more') }}"><i class="bi bi-cart-plus"></i></a>
                                    @endif
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr><td colspan="{{ 6 + $warehouses->count() }}" class="text-center text-body-secondary py-4">{{ __('No stock items found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($products->hasPages())
            <div class="card-footer">{{ $products->links() }}</div>
        @endif
    </div>
@endsection
