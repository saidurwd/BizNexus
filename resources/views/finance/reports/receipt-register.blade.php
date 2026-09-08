@extends('layouts.erp')

@section('title', 'Receipt Register')

@section('content_header')
    <h1>Receipt Register</h1>
    <div class="mt-2">
        <form method="GET" class="d-inline">
            <input type="date" name="start_date" class="form-control d-inline-block" style="width:auto;" value="{{ $startDate }}">
            <input type="date" name="end_date" class="form-control d-inline-block" style="width:auto;" value="{{ $endDate }}">
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>
        <button class="btn btn-secondary" onclick="window.print()">
            <i class="bi bi-printer"></i> Print
        </button>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Receipt #</th>
                        <th>Customer</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th class="text-right">Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $receipt)
                        <tr>
                            <td>{{ $receipt->receipt_date->format('Y-m-d') }}</td>
                            <td>{{ $receipt->receipt_number }}</td>
                            <td>{{ $receipt->customer?->name ?? '-' }}</td>
                            <td>{{ $receipt->receipt_method }}</td>
                            <td>{{ $receipt->reference ?? '-' }}</td>
                            <td class="text-right">{{ number_format($receipt->amount, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $receipt->status === 'POSTED' ? 'success' : ($receipt->status === 'PENDING' ? 'warning' : 'secondary') }}">
                                    {{ $receipt->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No receipts found</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="table-active">
                        <td colspan="5" class="text-end"><strong>Total</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalAmount, 2) }}</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
