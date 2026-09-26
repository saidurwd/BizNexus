@extends('layouts.erp')

@section('title', __('Dashboard'))

@section('content_header')
    <div class="d-flex flex-wrap align-items-baseline gap-2">
        <h1 class="m-0">{{ __('Welcome, :name', ['name' => auth()->user()->name]) }}</h1>
        @if ($company)
            <span class="text-body-secondary">{{ $company->name }} · {{ Formatter::date($today) }}</span>
        @endif
    </div>
@endsection

@section('content')
    @if ($summary)
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box text-bg-primary">
                    <div class="inner">
                        <h3>{{ Formatter::amount($summary['cash_and_equivalents']) }}</h3>
                        <p>{{ __('Cash and bank') }} <small>({{ $currency }})</small></p>
                    </div>
                    <i class="small-box-icon bi bi-bank" aria-hidden="true"></i>
                    @can('finance.reports.view')
                        <a href="{{ route('finance.reports.cash-flow') }}" class="small-box-footer link-light link-underline-opacity-0">{{ __('Cash flow') }} <i class="bi bi-arrow-right-circle"></i></a>
                    @endcan
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box text-bg-success">
                    <div class="inner">
                        <h3>{{ Formatter::amount($summary['accounts_receivable']) }}</h3>
                        <p>{{ __('Receivables') }} · {{ __(':amount overdue', ['amount' => Formatter::amount($summary['overdue_receivables'])]) }}</p>
                    </div>
                    <i class="small-box-icon bi bi-box-arrow-in-down-left" aria-hidden="true"></i>
                    @can('finance.reports.view')
                        <a href="{{ route('finance.ar-aging') }}" class="small-box-footer link-light link-underline-opacity-0">{{ __('Receivables ageing') }} <i class="bi bi-arrow-right-circle"></i></a>
                    @endcan
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box text-bg-warning">
                    <div class="inner">
                        <h3>{{ Formatter::amount($summary['accounts_payable']) }}</h3>
                        <p>{{ __('Payables') }} · {{ __(':amount due in 7 days', ['amount' => Formatter::amount($summary['payables_due_soon'])]) }}</p>
                    </div>
                    <i class="small-box-icon bi bi-box-arrow-up-right" aria-hidden="true"></i>
                    @can('finance.reports.view')
                        <a href="{{ route('finance.ap-aging') }}" class="small-box-footer link-dark link-underline-opacity-0">{{ __('Payables ageing') }} <i class="bi bi-arrow-right-circle"></i></a>
                    @endcan
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box {{ bccomp($summary['net_profit'], '0', 4) < 0 ? 'text-bg-danger' : 'text-bg-info' }}">
                    <div class="inner">
                        <h3>{{ Formatter::amount($summary['net_profit']) }}</h3>
                        <p>{{ __('Profit, fiscal year to date') }}</p>
                    </div>
                    <i class="small-box-icon bi bi-graph-up-arrow" aria-hidden="true"></i>
                    @can('finance.reports.view')
                        <a href="{{ route('finance.reports.profit-loss') }}" class="small-box-footer link-light link-underline-opacity-0">{{ __('Profit and loss') }} <i class="bi bi-arrow-right-circle"></i></a>
                    @endcan
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        @if ($performance)
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('Revenue and expenses, last 12 months') }} <small class="text-body-secondary">({{ $currency }})</small></h3>
                    </div>
                    <div class="card-body">
                        <canvas id="performance-chart" height="110" aria-label="{{ __('Revenue and expenses, last 12 months') }}" role="img"></canvas>
                    </div>
                </div>
            </div>
        @endif

        <div class="{{ $performance ? 'col-lg-4' : 'col-lg-6' }}">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Needs attention') }}</h3>
                </div>
                <ul class="list-group list-group-flush">
                    @if ($myApprovalCount > 0)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <a href="{{ route('workflow.index') }}">{{ __('Your approvals') }}</a>
                            <span class="badge text-bg-danger rounded-pill">{{ $myApprovalCount }}</span>
                        </li>
                    @endif
                    @foreach ($attentionItems as $item)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <a href="{{ route($item['route'], $item['parameters']) }}">{{ __($item['label']) }}</a>
                            <span class="badge text-bg-secondary rounded-pill">{{ $item['count'] }}</span>
                        </li>
                    @endforeach
                    @if ($myApprovalCount === 0 && $attentionItems->isEmpty())
                        <li class="list-group-item text-body-secondary">
                            <i class="bi bi-check2-circle text-success"></i> {{ __('Nothing is waiting for you.') }}
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Quick actions') }}</h3>
                </div>
                <div class="card-body d-flex flex-wrap gap-2">
                    @can('finance.customer-invoices.create')
                        <a href="{{ route('finance.customer-invoices.create') }}" class="btn btn-outline-primary"><i class="bi bi-receipt"></i> {{ __('New customer invoice') }}</a>
                    @endcan
                    @can('finance.receipts.create')
                        <a href="{{ route('finance.receipts.create') }}" class="btn btn-outline-primary"><i class="bi bi-cash-coin"></i> {{ __('Record a receipt') }}</a>
                    @endcan
                    @can('finance.supplier-invoices.create')
                        <a href="{{ route('finance.supplier-invoices.create') }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-text"></i> {{ __('New supplier invoice') }}</a>
                    @endcan
                    @can('finance.journals.create')
                        <a href="{{ route('finance.journals.create') }}" class="btn btn-outline-primary"><i class="bi bi-journal-plus"></i> {{ __('New journal') }}</a>
                    @endcan
                    @can('finance.reports.view')
                        <a href="{{ route('finance.reports.trial-balance') }}" class="btn btn-outline-secondary"><i class="bi bi-table"></i> {{ __('Trial balance') }}</a>
                    @endcan
                    <a href="{{ route('profile.edit') }}" class="btn btn-outline-secondary"><i class="bi bi-person-gear"></i> {{ __('Your profile and security') }}</a>
                </div>
            </div>
        </div>

        @if ($overdueCustomers->isNotEmpty())
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('Largest overdue customers') }} <small class="text-body-secondary">({{ $currency }})</small></h3>
                    </div>
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Customer') }}</th>
                                <th class="text-end">{{ __('Invoices') }}</th>
                                <th class="text-end">{{ __('Overdue') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($overdueCustomers as $row)
                                <tr>
                                    <td>{{ $row['customer'] }}</td>
                                    <td class="text-end">{{ $row['invoices'] }}</td>
                                    <td class="text-end">{{ Formatter::amount($row['amount']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection

@if ($performance)
    @push('js')
        <script type="module">
            new window.Chart(document.getElementById('performance-chart'), {
                type: 'bar',
                data: {
                    labels: @json($performance['labels']),
                    datasets: [
                        { label: @json(__('Revenue')), data: @json($performance['revenue']), backgroundColor: 'rgba(25, 135, 84, 0.7)' },
                        { label: @json(__('Expenses')), data: @json($performance['expenses']), backgroundColor: 'rgba(220, 53, 69, 0.7)' },
                    ],
                },
                options: {
                    responsive: true,
                    locale: document.documentElement.lang || undefined,
                    scales: { y: { beginAtZero: true } },
                },
            });
        </script>
    @endpush
@endif
