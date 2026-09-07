@extends('adminlte::page')

@section('title', 'Receipt Details')

@section('content_header')
    <h1>Receipt: {{ $receipt->receipt_number }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <table class="table table-sm">
                <tr>
                    <th width="200">Receipt Number</th>
                    <td>{{ $receipt->receipt_number }}</td>
                </tr>
                <tr>
                    <th>Receipt Date</th>
                    <td>{{ $receipt->receipt_date->format('Y-m-d') }}</td>
                </tr>
                <tr>
                    <th>Receipt Type</th>
                    <td>{{ $receipt->receipt_type }}</td>
                </tr>
                <tr>
                    <th>Receipt Account</th>
                    <td>{{ $receipt->receiptAccount?->account_name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Amount</th>
                    <td>{{ number_format($receipt->amount, 2) }}</td>
                </tr>
                <tr>
                    <th>Payer Type</th>
                    <td>{{ $receipt->payer_type }}</td>
                </tr>
                <tr>
                    <th>Payer Name</th>
                    <td>{{ $receipt->payer_name }}</td>
                </tr>
                <tr>
                    <th>Reference</th>
                    <td>{{ $receipt->reference ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Description</th>
                    <td>{{ $receipt->description ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="badge bg-{{ $receipt->status === 'COMPLETED' ? 'success' : ($receipt->status === 'PENDING' ? 'warning' : 'secondary') }}">
                            {{ $receipt->status }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('finance.receipts.index') }}" class="btn btn-secondary">Back</a>
    </div>
@endsection
