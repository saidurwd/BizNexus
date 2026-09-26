@extends('layouts.erp')

@section('title', __('Payments'))

@section('content_header')
    <h1>{{ __('Payments') }}</h1>
    <div class="mt-2">
        <a href="{{ route('finance.payments.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> {{ __('New Payment') }}
        </a>
    </div>
@endsection

@section('content')
    <x-finance.list-filters :filters="$filters" :statuses="['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED', 'POSTED', 'CANCELLED']" :search-label="__('Payment number, reference or supplier')" />
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Payment Register') }}</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Payment #</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Payee') }}</th>
                        <th>{{ __('Payment Account') }}</th>
                        <th class="text-end">{{ __('Amount') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_number }}</td>
                            <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                            <td>{{ $payment->payee_name }}</td>
                            <td>{{ $payment->bankAccount?->gl_account?->account_name ?? '-' }}</td>
                            <td class="text-end">{{ Formatter::amount($payment->amount, $payment->currency?->code) }}</td>
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
                            <td colspan="7" class="text-center">{{ __('No payments found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($payments->hasPages())
            <div class="card-footer">{{ $payments->links() }}</div>
        @endif
    </div>
@endsection
