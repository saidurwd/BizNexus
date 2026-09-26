@extends('layouts.erp')

@section('title', __('Payment Details'))

@section('content_header')
    <h1>{{ __('Payment :number', ['number' => $payment->payment_number]) }}</h1>
@endsection

@section('content')
    <x-print-toolbar />
    <x-print-document-header :title="__('Payment voucher')" :subtitle="$payment->payment_number" />

    <div class="card">
        <div class="card-body">
            <table class="table table-sm">
                <tr>
                    <th width="200">{{ __('Payment Number') }}</th>
                    <td>{{ $payment->payment_number }}</td>
                </tr>
                <tr>
                    <th>{{ __('Payment Date') }}</th>
                    <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                </tr>
                <tr>
                    <th>{{ __('Payment Type') }}</th>
                    <td>{{ $payment->payment_method }}</td>
                </tr>
                <tr>
                    <th>{{ __('Payment Account') }}</th>
                    <td>{{ $payment->bankAccount?->gl_account?->account_name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Amount') }}</th>
                    <td>{{ Formatter::amount($payment->amount, $payment->currency?->code) }}</td>
                </tr>
                <tr>
                    <th>{{ __('Payee Type') }}</th>
                    <td>{{ $payment->payee_type }}</td>
                </tr>
                <tr>
                    <th>{{ __('Payee Name') }}</th>
                    <td>{{ $payment->payee_name }}</td>
                </tr>
                <tr>
                    <th>{{ __('Reference') }}</th>
                    <td>{{ $payment->reference ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Description') }}</th>
                    <td>{{ $payment->description ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Status') }}</th>
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
        <a href="{{ route('finance.payments.index') }}" class="btn btn-secondary">{{ __('Back') }}</a>
    </div>
    <x-attachments :document="$payment" type="payments" />
    <x-print-signatures :labels="[__('Prepared by'), __('Approved by'), __('Received by')]" />
@endsection
