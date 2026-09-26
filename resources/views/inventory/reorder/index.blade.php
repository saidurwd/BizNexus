@extends('layouts.erp')

@section('title', __('Reorder Suggestions'))

@section('content_header')
    <h1>{{ __('Reorder Suggestions') }}</h1>
    <p class="text-body-secondary mb-0">{{ __('Stock items whose projected quantity (on hand, less reserved for customers, plus still to arrive from suppliers) is at or below their reorder level. Tick what to order: one draft purchase order is created per supplier.') }}</p>
@endsection

@section('content')
    <form method="POST" action="{{ route('inventory.reorder.store') }}">
        @csrf
        <div class="card">
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 30px"></th>
                            <th>{{ __('Product') }}</th>
                            <th class="text-end">{{ __('On hand') }}</th>
                            <th class="text-end">{{ __('Reserved') }}</th>
                            <th class="text-end">{{ __('On order') }}</th>
                            <th class="text-end">{{ __('Projected') }}</th>
                            <th class="text-end">{{ __('Reorder level') }}</th>
                            <th style="width: 130px" class="text-end">{{ __('Order qty') }}</th>
                            <th style="min-width: 220px">{{ __('Supplier') }}</th>
                            <th class="text-end">{{ __('Purchase price') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php
                                $product = $row['product'];
                                $decimals = $product->unit?->decimals ?? 0;
                                $field = fn (string $key) => "lines[{$product->id}][{$key}]";
                            @endphp
                            <tr>
                                <td><input type="checkbox" name="{{ $field('selected') }}" value="1" class="form-check-input" aria-label="{{ __('Order :product', ['product' => $product->sku]) }}" @checked(old("lines.{$product->id}.selected", bccomp($row['suggested'], '0', 4) > 0 && $product->preferred_supplier_id))></td>
                                <td><a href="{{ route('inventory.products.show', $product->id) }}"><code>{{ $product->sku }}</code></a> {{ $product->name }}</td>
                                <td class="text-end">{{ Formatter::quantity($row['on_hand'], $decimals) }}</td>
                                <td class="text-end">{{ Formatter::quantity($row['reserved'], $decimals) }}</td>
                                <td class="text-end">{{ Formatter::quantity($row['on_order'], $decimals) }}</td>
                                <td class="text-end fw-bold {{ bccomp($row['projected'], '0', 4) < 0 ? 'text-danger' : '' }}">{{ Formatter::quantity($row['projected'], $decimals) }}</td>
                                <td class="text-end">{{ Formatter::quantity($product->reorder_level, $decimals) }}</td>
                                <td><input type="number" name="{{ $field('quantity') }}" value="{{ old("lines.{$product->id}.quantity", (float) $row['suggested']) }}" class="form-control form-control-sm text-end" step="any" min="0" aria-label="{{ __('Order qty') }}"></td>
                                <td>
                                    <select name="{{ $field('supplier_id') }}" class="form-select form-select-sm" aria-label="{{ __('Supplier') }}">
                                        <option value="">{{ __('Select supplier') }}</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" @selected((string) old("lines.{$product->id}.supplier_id", $product->preferred_supplier_id) === (string) $supplier->id)>{{ $supplier->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="text-end">{{ Formatter::unitPrice($product->purchase_price) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-body-secondary py-4">{{ __('Nothing needs reordering. Set reorder levels on products to get suggestions.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($rows->isNotEmpty())
                <div class="card-footer d-flex flex-wrap gap-2 align-items-end">
                    <div>
                        <label for="warehouse_id" class="form-label mb-1">{{ __('Deliver to') }}</label>
                        <select id="warehouse_id" name="warehouse_id" class="form-select">
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->code }} — {{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-cart-plus"></i> {{ __('Create draft purchase orders') }}</button>
                </div>
            @endif
        </div>
    </form>
@endsection
