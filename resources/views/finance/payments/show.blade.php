@extends('layouts.erp')

@section('title', 'Payment Details')

@section('content_header')
    <h1>Payment: {{ $payment->payment_number }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <table class="table table-sm">
                <tr>
                    <th width="200">Payment Number</th>
                    <td>{{ $payment->payment_number }}</td>
                </tr>
                <tr>
                    <th>Payment Date</th>
                    <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                </tr>
                <tr>
                    <th>Payment Type</th>
                    <td>{{ $payment->payment_method }}</td>
                </tr>
                <tr>
                    <th>Payment Account</th>
                    <td>{{ $payment->bankAccount?->gl_account?->account_name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Amount</th>
                    <td>{{ number_format($payment->amount, 2) }}</td>
                </tr>
                <tr>
                    <th>Payee Type</th>
                    <td>{{ $payment->payee_type }}</td>
                </tr>
                <tr>
                    <th>Payee Name</th>
                    <td>{{ $payment->payee_name }}</td>
                </tr>
                <tr>
                    <th>Reference</th>
                    <td>{{ $payment->reference ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Description</th>
                    <td>{{ $payment->description ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="badge bg-{{ $payment->status === 'POSTED' ? 'success' : ($payment->status === 'DRAFT' ? 'secondary' : 'warning') }}">
                            {{ $payment->status }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('finance.payments.index') }}" class="btn btn-secondary">Back</a>
    </div>
@endsection
