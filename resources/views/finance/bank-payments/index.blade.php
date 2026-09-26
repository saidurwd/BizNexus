@extends('layouts.erp')

@section('title', __('Bank Payments - BizNexus'))

@section('content_header')
    <h1>{{ __('Bank Payments') }}</h1>
    <div class="mt-2">
        <a href="{{ route('finance.bank-payments.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> {{ __('New Bank Payment') }}
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
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Bank Account') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th class="text-end">{{ __('Amount') }}</th>
                        <th>{{ __('Reference') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->transaction_number }}</td>
                            <td>{{ $payment->transaction_date->format('Y-m-d') }}</td>
                            <td>{{ $payment->bankAccount?->bank_name ?? '-' }} — {{ $payment->bankAccount?->display_account_number ?? '-' }}</td>
                            <td>{{ $payment->transaction_type }}</td>
                            <td class="text-end">{{ Formatter::amount($payment->amount) }}</td>
                            <td>{{ $payment->reference ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $payment->status === 'COMPLETED' ? 'success' : ($payment->status === 'PENDING' ? 'warning' : 'secondary') }}">
                                    {{ $payment->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">{{ __('No bank payments found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
