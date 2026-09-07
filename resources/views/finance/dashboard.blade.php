@extends('adminlte::page')

@section('title', 'Finance Dashboard')

@section('content_header')
    <h1>Finance Dashboard</h1>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format($dashboard['total_revenue'] ?? 0, 2) }}</h3>
                    <p>Total Revenue</p>
                </div>
                <div class="icon">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format($dashboard['total_expenses'] ?? 0, 2) }}</h3>
                    <p>Total Expenses</p>
                </div>
                <div class="icon">
                    <i class="bi bi-graph-down-arrow"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ number_format($dashboard['net_profit'] ?? 0, 2) }}</h3>
                    <p>Net Profit</p>
                </div>
                <div class="icon">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number_format($dashboard['total_assets'] ?? 0, 2) }}</h3>
                    <p>Total Assets</p>
                </div>
                <div class="icon">
                    <i class="bi bi-building"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Key Financial Metrics</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Cash Balance:</strong></td>
                            <td class="text-right">{{ number_format($dashboard['cash_balance'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Bank Balance:</strong></td>
                            <td class="text-right">{{ number_format($dashboard['bank_balance'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Accounts Receivable:</strong></td>
                            <td class="text-right">{{ number_format($dashboard['accounts_receivable'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Accounts Payable:</strong></td>
                            <td class="text-right">{{ number_format($dashboard['accounts_payable'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Total Liabilities:</strong></td>
                            <td class="text-right">{{ number_format($dashboard['total_liabilities'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Total Equity:</strong></td>
                            <td class="text-right">{{ number_format($dashboard['total_equity'] ?? 0, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="{{ route('finance.journals.create') }}" class="list-group-item list-group-item-action">
                            <i class="bi bi-plus-circle me-2"></i> New Journal Entry
                        </a>
                        <a href="{{ route('finance.accounts.index') }}" class="list-group-item list-group-item-action">
                            <i class="bi bi-diagram-3 me-2"></i> Chart of Accounts
                        </a>
                        <a href="{{ route('finance.suppliers.index') }}" class="list-group-item list-group-item-action">
                            <i class="bi bi-people me-2"></i> Manage Suppliers
                        </a>
                        <a href="{{ route('finance.customers.index') }}" class="list-group-item list-group-item-action">
                            <i class="bi bi-people me-2"></i> Manage Customers
                        </a>
                        <a href="{{ route('finance.reports.trial-balance') }}" class="list-group-item list-group-item-action">
                            <i class="bi bi-bar-chart me-2"></i> Trial Balance Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
