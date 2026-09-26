@extends('layouts.erp')

@section('title', __('Inventory Dashboard'))

@section('content_header')
    <div class="d-flex flex-wrap align-items-baseline gap-2">
        <h1 class="m-0">{{ __('Inventory Dashboard') }}</h1>
        <span class="text-body-secondary">{{ Formatter::date($today) }} · {{ __('Values in :currency at weighted average cost', ['currency' => $currency]) }}</span>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-primary">
                <div class="inner">
                    <h3>{{ Formatter::amount($summary['stock_value']) }}</h3>
                    <p>{{ trans_choice('Stock value · :count item in stock|Stock value · :count items in stock', $summary['items_in_stock'], ['count' => $summary['items_in_stock']]) }}</p>
                </div>
                <i class="small-box-icon bi bi-boxes" aria-hidden="true"></i>
                <a href="{{ route('inventory.stock.valuation') }}" class="small-box-footer link-light link-underline-opacity-0">{{ __('Inventory valuation') }} <i class="bi bi-arrow-right-circle"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box {{ $summary['below_reorder'] > 0 ? 'text-bg-danger' : 'text-bg-success' }}">
                <div class="inner">
                    <h3>{{ $summary['below_reorder'] }}</h3>
                    <p>{{ __('Items to reorder') }}</p>
                </div>
                <i class="small-box-icon bi bi-cart-plus" aria-hidden="true"></i>
                @can('inventory.purchase-orders.create')
                    <a href="{{ route('inventory.reorder.index') }}" class="small-box-footer link-light link-underline-opacity-0">{{ __('Reorder Suggestions') }} <i class="bi bi-arrow-right-circle"></i></a>
                @endcan
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-warning">
                <div class="inner">
                    <h3>{{ Formatter::amount($summary['slow_value']) }}</h3>
                    <p>{{ __('Stock not sold for 90 days') }}</p>
                </div>
                <i class="small-box-icon bi bi-hourglass-split" aria-hidden="true"></i>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-info">
                <div class="inner">
                    <h3>{{ $summary['turnover'] !== null ? Formatter::number($summary['turnover'], 1).'×' : '—' }}</h3>
                    <p>{{ __('Stock turnover, last 12 months') }}@if ($summary['days_of_cover'] !== null) · {{ __(':days days of cover', ['days' => $summary['days_of_cover']]) }}@endif</p>
                </div>
                <i class="small-box-icon bi bi-arrow-repeat" aria-hidden="true"></i>
                <a href="{{ route('inventory.stock.movements') }}" class="small-box-footer link-light link-underline-opacity-0">{{ __('Stock movements') }} <i class="bi bi-arrow-right-circle"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title">{{ __('Stock value by days since last receipt') }}</h3></div>
                <div class="card-body">
                    <canvas id="ageing-chart" height="200" role="img" aria-label="{{ __('Stock value by days since last receipt') }}"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title">{{ __('Highest stock value') }}</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>{{ __('Product') }}</th><th class="text-end">{{ __('On hand') }}</th><th class="text-end">{{ __('Value') }}</th></tr></thead>
                        <tbody>
                            @forelse ($topByValue as $product)
                                <tr>
                                    <td><a href="{{ route('inventory.products.show', $product->id) }}"><code>{{ $product->sku }}</code></a> {{ $product->name }}</td>
                                    <td class="text-end">{{ Formatter::quantity($product->stock_quantity, $product->unit?->decimals ?? 0) }} {{ $product->unit?->code }}</td>
                                    <td class="text-end">{{ Formatter::amount($product->stock_value) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-body-secondary py-3">{{ __('No stock items found.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">{{ __('Slow movers: in stock, not sold for 90 days') }}</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>{{ __('Product') }}</th><th class="text-end">{{ __('On hand') }}</th><th class="text-end">{{ __('Value') }}</th><th>{{ __('Last sold') }}</th></tr></thead>
                <tbody>
                    @forelse ($slowMovers as $row)
                        <tr>
                            <td><a href="{{ route('inventory.products.show', $row['product']->id) }}"><code>{{ $row['product']->sku }}</code></a> {{ $row['product']->name }}</td>
                            <td class="text-end">{{ Formatter::quantity($row['product']->stock_quantity, $row['product']->unit?->decimals ?? 0) }} {{ $row['product']->unit?->code }}</td>
                            <td class="text-end">{{ Formatter::amount($row['product']->stock_value) }}</td>
                            <td>{{ $row['last_sold_on'] ? Formatter::date($row['last_sold_on']) : __('Never') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-body-secondary py-3">{{ __('Everything in stock has sold in the last 90 days.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('js')
    <script type="module">
        new window.Chart(document.getElementById('ageing-chart'), {
            type: 'doughnut',
            data: {
                labels: @json(array_map(fn (string $bucket) => __(':range days', ['range' => $bucket]), array_keys($ageing))),
                datasets: [{ data: @json(array_map(fn ($value) => round((float) $value, 2), array_values($ageing))), backgroundColor: ['#198754', '#0d6efd', '#ffc107', '#dc3545'] }],
            },
            options: { responsive: true, locale: document.documentElement.lang || undefined },
        });
    </script>
@endpush
