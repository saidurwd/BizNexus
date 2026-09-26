@extends('layouts.erp')

@section('title', __('Sales Dashboard'))

@section('content_header')
    <div class="d-flex flex-wrap align-items-baseline gap-2">
        <h1 class="m-0">{{ __('Sales Dashboard') }}</h1>
        <span class="text-body-secondary">{{ Formatter::date($today) }} · {{ __('Amounts in :currency, before tax', ['currency' => $currency]) }}</span>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-secondary">
                <div class="inner">
                    <h3>{{ Formatter::amount($summary['quotation_value']) }}</h3>
                    <p>{{ trans_choice(':count open quotation|:count open quotations', $summary['open_quotations'], ['count' => $summary['open_quotations']]) }}</p>
                </div>
                <i class="small-box-icon bi bi-file-earmark-richtext" aria-hidden="true"></i>
                @can('sales.quotations.view')
                    <a href="{{ route('sales.quotations.index') }}" class="small-box-footer link-light link-underline-opacity-0">{{ __('Quotations') }} <i class="bi bi-arrow-right-circle"></i></a>
                @endcan
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-primary">
                <div class="inner">
                    <h3>{{ Formatter::amount($summary['backlog_value']) }}</h3>
                    <p>{{ __('Order backlog (not yet delivered)') }}</p>
                </div>
                <i class="small-box-icon bi bi-clipboard-check" aria-hidden="true"></i>
                <a href="{{ route('sales.orders.index', ['status' => 'CONFIRMED']) }}" class="small-box-footer link-light link-underline-opacity-0">{{ __('Sales Orders') }} <i class="bi bi-arrow-right-circle"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-warning">
                <div class="inner">
                    <h3>{{ Formatter::amount($summary['to_invoice_value']) }}</h3>
                    <p>{{ __('Delivered, not yet invoiced') }}</p>
                </div>
                <i class="small-box-icon bi bi-receipt" aria-hidden="true"></i>
                <a href="{{ route('sales.orders.index', ['status' => 'DELIVERED']) }}" class="small-box-footer link-dark link-underline-opacity-0">{{ __('Sales Orders') }} <i class="bi bi-arrow-right-circle"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-success">
                <div class="inner">
                    <h3>{{ Formatter::amount($summary['sales_this_month']) }}</h3>
                    <p>
                        {{ __('Sales this month') }}
                        @if ($summary['margin_percent'] !== null)
                            · {{ __(':percent% gross margin', ['percent' => Formatter::percent($summary['margin_percent'])]) }}
                        @endif
                    </p>
                </div>
                <i class="small-box-icon bi bi-graph-up-arrow" aria-hidden="true"></i>
                @can('finance.customer-invoices.view')
                    <a href="{{ route('finance.customer-invoices.index') }}" class="small-box-footer link-light link-underline-opacity-0">{{ __('Customer Invoices') }} <i class="bi bi-arrow-right-circle"></i></a>
                @endcan
            </div>
        </div>
    </div>

    @if ($summary['unconverted'] > 0)
        <div class="alert alert-warning">{{ trans_choice(':count document in a foreign currency has no exchange rate and is left out.|:count documents in a foreign currency have no exchange rate and are left out.', $summary['unconverted'], ['count' => $summary['unconverted']]) }}</div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title">{{ __('Sales and gross margin, last 12 months') }}</h3></div>
                <div class="card-body">
                    <canvas id="sales-chart" height="110" role="img" aria-label="{{ __('Sales and gross margin, last 12 months') }}"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title">{{ __('Due for delivery within 7 days') }}</h3></div>
                <ul class="list-group list-group-flush">
                    @forelse ($dueOrders as $order)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                <a href="{{ route('sales.orders.show', $order->id) }}">{{ $order->order_number }}</a>
                                <small class="text-body-secondary d-block">{{ $order->customer?->name }}</small>
                            </span>
                            <span class="badge {{ $order->delivery_date->lt($today) ? 'text-bg-danger' : 'text-bg-light' }}">{{ Formatter::date($order->delivery_date) }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-body-secondary">{{ __('Nothing due.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title">{{ __('Top customers, last 12 months') }}</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>{{ __('Customer') }}</th><th class="text-end">{{ __('Sales') }}</th><th class="text-end">{{ __('Gross margin') }}</th></tr></thead>
                        <tbody>
                            @forelse ($topCustomers as $row)
                                <tr><td>{{ $row['name'] }}</td><td class="text-end">{{ Formatter::amount($row['revenue']) }}</td><td class="text-end">{{ Formatter::amount($row['margin']) }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-body-secondary py-3">{{ __('No sales yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title">{{ __('Most profitable products, last 12 months') }}</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>{{ __('Product') }}</th><th class="text-end">{{ __('Sales') }}</th><th class="text-end">{{ __('Gross margin') }}</th><th class="text-end">%</th></tr></thead>
                        <tbody>
                            @forelse ($topProducts as $row)
                                <tr>
                                    <td>{{ $row['name'] }}</td>
                                    <td class="text-end">{{ Formatter::amount($row['revenue']) }}</td>
                                    <td class="text-end">{{ Formatter::amount($row['margin']) }}</td>
                                    <td class="text-end">{{ $row['margin_percent'] !== null ? Formatter::percent($row['margin_percent']).'%' : '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-body-secondary py-3">{{ __('No sales yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script type="module">
        new window.Chart(document.getElementById('sales-chart'), {
            type: 'bar',
            data: {
                labels: @json($months->pluck('label')->values()),
                datasets: [
                    { label: @json(__('Sales')), data: @json($months->pluck('revenue')->map(fn ($value) => round((float) $value, 2))->values()), backgroundColor: 'rgba(13, 110, 253, 0.7)' },
                    { label: @json(__('Gross margin')), data: @json($months->pluck('margin')->map(fn ($value) => round((float) $value, 2))->values()), backgroundColor: 'rgba(25, 135, 84, 0.7)' },
                ],
            },
            options: { responsive: true, locale: document.documentElement.lang || undefined, scales: { y: { beginAtZero: true } } },
        });
    </script>
@endpush
