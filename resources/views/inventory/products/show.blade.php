@extends('layouts.erp')

@php
    $accountRoles = [
        'inventory' => __('Inventory account'),
        'cogs' => __('Cost of goods sold account'),
        'revenue' => __('Revenue account'),
        'expense' => __('Expense account (non-stock purchases)'),
    ];
    $decimals = $product->unit?->decimals ?? 0;
@endphp

@section('title', $product->name)

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="m-0">{{ $product->name }} <small class="text-body-secondary">{{ $product->sku }}</small> <x-status-badge :status="$product->status" class="fs-6" /></h1>
        <div class="d-flex gap-2">
            @can('inventory.purchase-orders.create')
                <a href="{{ route('inventory.purchase-orders.create', ['product' => $product->id]) }}" class="btn btn-outline-secondary"><i class="bi bi-cart-plus"></i> {{ __('Order more') }}</a>
            @endcan
            @can('inventory.products.manage')
                <a href="{{ route('inventory.products.edit', $product->id) }}" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> {{ __('Edit') }}</a>
                @unless ($product->isInUse())
                    <form method="POST" action="{{ route('inventory.products.destroy', $product->id) }}" onsubmit="return confirm(@js(__('Delete this product?')))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i> {{ __('Delete') }}</button>
                    </form>
                @endunless
            @endcan
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">{{ __('Type') }}</dt>
                        <dd class="col-sm-8">{{ $product->typeLabel() }}</dd>
                        <dt class="col-sm-4">{{ __('Category') }}</dt>
                        <dd class="col-sm-8">{{ $product->category?->name ?? '—' }}</dd>
                        <dt class="col-sm-4">{{ __('Unit of measure') }}</dt>
                        <dd class="col-sm-8">{{ $product->unit?->code }} — {{ $product->unit?->name }}</dd>
                        <dt class="col-sm-4">{{ __('Barcode') }}</dt>
                        <dd class="col-sm-8">{{ $product->barcode ?? '—' }}</dd>
                        <dt class="col-sm-4">{{ __('Description') }}</dt>
                        <dd class="col-sm-8">{!! nl2br(e($product->description ?? '—')) !!}</dd>
                        <dt class="col-sm-4">{{ __('Purchase price') }}</dt>
                        <dd class="col-sm-8">{{ Formatter::unitPrice($product->purchase_price) }} <span class="text-body-secondary">· {{ $product->purchaseTax?->tax_code ?? __('By tax rules') }}</span></dd>
                        <dt class="col-sm-4">{{ __('Sales price') }}</dt>
                        <dd class="col-sm-8">{{ Formatter::unitPrice($product->sales_price) }} <span class="text-body-secondary">· {{ $product->salesTax?->tax_code ?? __('By tax rules') }}</span></dd>
                        <dt class="col-sm-4">{{ __('Preferred supplier') }}</dt>
                        <dd class="col-sm-8">{{ $product->preferredSupplier?->name ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ __('Ledger accounts') }}</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        @foreach ($accountRoles as $role => $label)
                            @php
                                $own = $product->{\Illuminate\Support\Str::camel($role).'Account'};
                                $account = $own ?? $product->category?->{\Illuminate\Support\Str::camel($role).'Account'};
                            @endphp
                            <tr>
                                <th class="w-50">{{ $label }}</th>
                                <td>
                                    @if ($account)
                                        {{ $account->account_code }} — {{ $account->account_name }}
                                        @unless ($own)<span class="badge text-bg-light">{{ __('From category') }}</span>@endunless
                                    @else
                                        <span class="text-body-secondary">{{ __('Not set') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            @if ($product->isStocked())
                <div class="card mb-3">
                    <table class="table table-sm mb-0">
                        <tr>
                            <th>{{ __('On hand') }}</th>
                            <td class="text-end fw-bold">{{ Formatter::quantity($product->stock_quantity, $decimals) }} {{ $product->unit?->code }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Reserved for sales orders') }}</th>
                            <td class="text-end">{{ Formatter::quantity($reserved, $decimals) }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Available') }}</th>
                            <td class="text-end">{{ Formatter::quantity(bcsub((string) $product->stock_quantity, $reserved, 4), $decimals) }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Average cost') }}</th>
                            <td class="text-end">{{ Formatter::unitPrice($product->averageCost()) }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Stock value') }}</th>
                            <td class="text-end">{{ Formatter::amount($product->stock_value) }}</td>
                        </tr>
                        <tr class="{{ $product->isBelowReorderLevel() ? 'table-warning' : '' }}">
                            <th>{{ __('Reorder level') }}</th>
                            <td class="text-end">{{ $product->reorder_level !== null ? Formatter::quantity($product->reorder_level, $decimals) : '—' }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Reorder quantity') }}</th>
                            <td class="text-end">{{ $product->reorder_quantity !== null ? Formatter::quantity($product->reorder_quantity, $decimals) : '—' }}</td>
                        </tr>
                    </table>
                    <div class="card-footer small text-body-secondary">{{ __('Weighted average cost in the functional currency (IAS 2).') }}</div>
                </div>

                <div class="card mb-3">
                    <div class="card-header"><h3 class="card-title">{{ __('Quantities by warehouse') }}</h3></div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            @forelse ($balances as $balance)
                                <tr>
                                    <td>{{ $balance->warehouse?->code }} — {{ $balance->warehouse?->name }}</td>
                                    <td class="text-end">{{ Formatter::quantity($balance->quantity, $decimals) }}</td>
                                </tr>
                            @empty
                                <tr><td class="text-body-secondary">{{ __('Not in stock.') }}</td></tr>
                            @endforelse
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
    @if ($product->isStocked())
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h3 class="card-title">{{ __('Latest stock movements') }}</h3>
                @can('inventory.stock.view')
                    <a href="{{ route('inventory.stock.movements', ['product' => $product->id]) }}">{{ __('All movements') }}</a>
                @endcan
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Document') }}</th>
                            <th>{{ __('Warehouse') }}</th>
                            <th class="text-end">{{ __('Quantity') }}</th>
                            <th class="text-end">{{ __('Value') }}</th>
                            <th class="text-end">{{ __('Balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($moves as $move)
                            <tr>
                                <td>{{ Formatter::date($move->move_date) }}</td>
                                <td>{{ $move->sourceLabel() }} <a href="{{ $move->sourceUrl() }}">{{ $move->reference }}</a></td>
                                <td>{{ $move->warehouse?->code }}</td>
                                <td class="text-end">{{ Formatter::quantity($move->quantity, $decimals) }}</td>
                                <td class="text-end">{{ Formatter::amount($move->value) }}</td>
                                <td class="text-end">{{ Formatter::quantity($move->quantity_after, $decimals) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-body-secondary py-3">{{ __('No stock movements found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
