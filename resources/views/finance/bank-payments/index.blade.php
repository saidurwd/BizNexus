@extends('layouts.erp')

@section('title', 'Bank Payments - BizNexus')

@section('content_header')
    <h1>Bank Payments</h1>
    <div class="mt-2">
        <a href="{{ route('finance.bank-payments.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Bank Payment
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
                        <th>Type</th>
                        <th class="text-right">Amount</th>
                        <th>Reference</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->transaction_number }}</td>
                            <td>{{ $payment->transaction_date->format('Y-m-d') }}</td>
                            <td>{{ $payment->bankAccount?->bank_name ?? '-' }} — {{ $payment->bankAccount?->account_number ?? '-' }}</td>
                            <td>{{ $payment->transaction_type }}</td>
                            <td class="text-right">{{ number_format($payment->amount, 2) }}</td>
                            <td>{{ $payment->reference ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $payment->status === 'COMPLETED' ? 'success' : ($payment->status === 'PENDING' ? 'warning' : 'secondary') }}">
                                    {{ $payment->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No bank payments found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
