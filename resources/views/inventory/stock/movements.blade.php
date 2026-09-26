@extends('layouts.erp')

@section('title', __('Stock Movements'))

@section('content_header')
    <h1>{{ __('Stock Movements') }}</h1>
    <p class="text-body-secondary mb-0">{{ __('Every receipt, issue, adjustment and transfer, valued in the functional currency.') }}</p>
@endsection

@section('content')
    <x-print-toolbar />
    <x-print-document-header :title="__('Stock Movements')" />
    <form method="GET" class="card mb-3">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-3">
                <label for="product" class="form-label">{{ __('Product') }}</label>
                <select id="product" name="product" class="form-select" data-searchable>
                    <option value="">{{ __('All') }}</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected((string) ($filters['product'] ?? '') === (string) $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
                    @endforeach
                </select>
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
                <label for="source" class="form-label">{{ __('Type') }}</label>
                <select id="source" name="source" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    @foreach (\Modules\Inventory\Models\StockMove::sourceLabels() as $source => $label)
                        <option value="{{ $source }}" @selected(($filters['source'] ?? '') === $source)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="from" class="form-label">{{ __('From') }}</label>
                <input type="date" id="from" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-2">
                <label for="to" class="form-label">{{ __('To') }}</label>
                <input type="date" id="to" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control">
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-primary">{{ __('Filter') }}</button></div>
        </div>
    </form>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Product') }}</th>
                        <th>{{ __('Warehouse') }}</th>
                        <th>{{ __('Document') }}</th>
                        <th class="text-end">{{ __('Quantity') }}</th>
                        <th class="text-end">{{ __('Unit cost') }}</th>
                        <th class="text-end">{{ __('Value') }}</th>
                        <th class="text-end">{{ __('Balance') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($moves as $move)
                        @php $decimals = $move->product?->unit?->decimals ?? 0; @endphp
                        <tr>
                            <td>{{ Formatter::date($move->move_date) }}</td>
                            <td><a href="{{ route('inventory.products.show', $move->product_id) }}">{{ $move->product?->sku }}</a></td>
                            <td>{{ $move->warehouse?->code }}</td>
                            <td>{{ $move->sourceLabel() }} <a href="{{ $move->sourceUrl() }}">{{ $move->reference }}</a></td>
                            <td class="text-end {{ bccomp((string) $move->quantity, '0', 4) < 0 ? 'text-danger' : 'text-success' }}">{{ Formatter::quantity($move->quantity, $decimals) }}</td>
                            <td class="text-end">{{ Formatter::unitPrice($move->unit_cost) }}</td>
                            <td class="text-end">{{ Formatter::amount($move->value) }}</td>
                            <td class="text-end">{{ Formatter::quantity($move->quantity_after, $decimals) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-body-secondary py-4">{{ __('No stock movements found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($moves->hasPages())
            <div class="card-footer">{{ $moves->links() }}</div>
        @endif
    </div>
@endsection
