@extends('layouts.erp')

@section('title', __('Receipt Details'))

@section('content_header')
    <h1>{{ __('Receipt :number', ['number' => $receipt->receipt_number]) }}</h1>
@endsection

@section('content')
    <x-print-toolbar />
    <x-print-document-header :title="__('Receipt voucher')" :subtitle="$receipt->receipt_number" />

    <div class="card">
        <div class="card-body">
            <table class="table table-sm">
                <tr>
                    <th width="200">{{ __('Receipt Number') }}</th>
                    <td>{{ $receipt->receipt_number }}</td>
                </tr>
                <tr>
                    <th>{{ __('Receipt Date') }}</th>
                    <td>{{ $receipt->receipt_date->format('Y-m-d') }}</td>
                </tr>
                <tr>
                    <th>{{ __('Receipt Method') }}</th>
                    <td>{{ $receipt->receipt_method }}</td>
                </tr>
                <tr>
                    <th>{{ __('Bank Account') }}</th>
                    <td>{{ $receipt->bankAccount?->account_name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Amount') }}</th>
                    <td>{{ Formatter::amount($receipt->amount, $receipt->currency?->code) }}</td>
                </tr>
                <tr>
                    <th>{{ __('Customer') }}</th>
                    <td>{{ $receipt->customer?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Reference') }}</th>
                    <td>{{ $receipt->reference ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Description') }}</th>
                    <td>{{ $receipt->description ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Status') }}</th>
                    <td>
                        <span class="badge bg-{{ $receipt->status === 'POSTED' ? 'success' : ($receipt->status === 'DRAFT' ? 'secondary' : 'warning') }}">
                            {{ $receipt->status }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('finance.receipts.index') }}" class="btn btn-secondary">{{ __('Back') }}</a>
    </div>
    <x-attachments :document="$receipt" type="receipts" />
    <x-print-signatures :labels="[__('Received by'), __('Checked by'), __('Approved by')]" />
@endsection
