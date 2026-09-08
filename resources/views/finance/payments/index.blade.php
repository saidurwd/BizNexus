@extends('layouts.erp')

@section('title', 'Payments')

@section('content_header')
    <h1>Payments</h1>
    <div class="mt-2">
        <a href="{{ route('finance.payments.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Payment
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Payment Register</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Payment #</th>
                        <th>Date</th>
                        <th>Payee</th>
                        <th>Payment Account</th>
                        <th class="text-right">Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_number }}</td>
                            <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                            <td>{{ $payment->payee_name }}</td>
                            <td>{{ $payment->bankAccount?->gl_account?->account_name ?? '-' }}</td>
                            <td class="text-right">{{ number_format($payment->amount, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $payment->status === 'POSTED' ? 'success' : ($payment->status === 'DRAFT' ? 'secondary' : 'warning') }}">
                                    {{ $payment->status }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('finance.payments.show', $payment->id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No payments found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
