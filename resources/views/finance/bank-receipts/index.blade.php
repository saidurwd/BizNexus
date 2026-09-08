@extends('layouts.erp')

@section('title', 'Bank Receipts - BizNexus')

@section('content_header')
    <h1>Bank Receipts</h1>
    <div class="mt-2">
        <a href="{{ route('finance.bank-receipts.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Bank Receipt
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Transaction #</th>
                        <th>Date</th>
                        <th>Bank Account</th>
                        <th class="text-right">Amount</th>
                        <th>Reference</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $receipt)
                        <tr>
                            <td>{{ $receipt->transaction_number }}</td>
                            <td>{{ $receipt->transaction_date->format('Y-m-d') }}</td>
                            <td>{{ $receipt->bankAccount?->bank_name ?? '-' }} — {{ $receipt->bankAccount?->account_number ?? '-' }}</td>
                            <td class="text-right">{{ number_format($receipt->amount, 2) }}</td>
                            <td>{{ $receipt->reference ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $receipt->status === 'COMPLETED' ? 'success' : ($receipt->status === 'PENDING' ? 'warning' : 'secondary') }}">
                                    {{ $receipt->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No bank receipts found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
