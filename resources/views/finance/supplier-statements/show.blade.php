@extends('layouts.erp')

@section('title', 'Supplier Statement - ' . $supplier->name)

@section('content_header')
    <h1>Supplier Statement: {{ $supplier->name }}</h1>
    <div class="mt-2">
        <a href="{{ route('finance.supplier-statements.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <button class="btn btn-primary" onclick="window.print()">
            <i class="bi bi-printer"></i> Print
        </button>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('finance.supplier-statements.show', $supplier->id) }}" class="row g-3 mb-3">
                <div class="col-md-3">
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" placeholder="Start Date">
                </div>
                <div class="col-md-3">
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" placeholder="End Date">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>

            <div class="row mb-3">
                <div class="col-md-3">
                    <strong>Supplier:</strong> {{ $supplier->name }}
                </div>
                <div class="col-md-3">
                    <strong>Code:</strong> {{ $supplier->supplier_code }}
                </div>
                <div class="col-md-3">
                    <strong>Period:</strong> {{ $startDate ?? 'Beginning' }} to {{ $endDate ?? 'Current' }}
                </div>
                <div class="col-md-3 text-end">
                    <strong>Closing Balance:</strong> {{ number_format($statement['closing_balance'], 2) }}
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Number</th>
                            <th>Description</th>
                            <th class="text-right">Debit</th>
                            <th class="text-right">Credit</th>
                            <th class="text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-active">
                            <td colspan="4"><strong>Opening Balance</strong></td>
                            <td class="text-right"><strong>{{ number_format($statement['opening_balance'], 2) }}</strong></td>
                            <td class="text-right">-</td>
                            <td class="text-right"><strong>{{ number_format($statement['opening_balance'], 2) }}</strong></td>
                        </tr>
                        @foreach($statement['entries'] as $entry)
                            <tr>
                                <td>{{ $entry['date'] }}</td>
                                <td>
                                    <span class="badge bg-{{ $entry['type'] === 'invoice' ? 'primary' : 'success' }}">
                                        {{ ucfirst($entry['type']) }}
                                    </span>
                                </td>
                                <td>{{ $entry['number'] }}</td>
                                <td>{{ $entry['description'] }}</td>
                                <td class="text-right">{{ $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '-' }}</td>
                                <td class="text-right">{{ $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '-' }}</td>
                                <td class="text-right">{{ number_format($entry['balance'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="table-active">
                            <td colspan="4"><strong>Closing Balance</strong></td>
                            <td class="text-right"><strong>{{ number_format($statement['total_invoices'], 2) }}</strong></td>
                            <td class="text-right"><strong>{{ number_format($statement['total_payments'], 2) }}</strong></td>
                            <td class="text-right"><strong>{{ number_format($statement['closing_balance'], 2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
