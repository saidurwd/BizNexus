@extends('adminlte::page')

@section('title', 'Receipts')

@section('content_header')
    <h1>Receipts</h1>
    <div class="mt-2">
        <a href="{{ route('finance.receipts.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Receipt
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Receipt Register</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Bank Account</th>
                        <th class="text-right">Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $receipt)
                        <tr>
                            <td>{{ $receipt->receipt_number }}</td>
                            <td>{{ $receipt->receipt_date->format('Y-m-d') }}</td>
                            <td>{{ $receipt->customer?->name ?? '-' }}</td>
                            <td>{{ $receipt->bankAccount?->account_name ?? '-' }}</td>
                            <td class="text-right">{{ number_format($receipt->amount, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $receipt->status === 'POSTED' ? 'success' : ($receipt->status === 'DRAFT' ? 'secondary' : 'warning') }}">
                                    {{ $receipt->status }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('finance.receipts.show', $receipt->id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No receipts found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
