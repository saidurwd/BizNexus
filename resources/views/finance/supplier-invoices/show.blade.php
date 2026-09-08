@extends('layouts.erp')

@section('title', 'Supplier Invoice')

@section('content_header')
    <h1>Invoice: {{ $invoice->invoice_number }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <table class="table table-sm">
                <tr>
                    <th width="200">Invoice Number</th>
                    <td>{{ $invoice->invoice_number }}</td>
                </tr>
                <tr>
                    <th>Invoice Date</th>
                    <td>{{ $invoice->invoice_date->format('Y-m-d') }}</td>
                </tr>
                <tr>
                    <th>Due Date</th>
                    <td>{{ $invoice->due_date?->format('Y-m-d') ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Supplier</th>
                    <td>{{ $invoice->supplier?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Subtotal</th>
                    <td class="text-right">{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <th>Tax Amount</th>
                    <td class="text-right">{{ number_format($invoice->tax_amount, 2) }}</td>
                </tr>
                <tr>
                    <th>Total Amount</th>
                    <td class="text-right">{{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="badge bg-{{ $invoice->status === 'PAID' ? 'success' : ($invoice->status === 'APPROVED' ? 'info' : ($invoice->status === 'DRAFT' ? 'secondary' : 'warning')) }}">
                            {{ $invoice->status }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('finance.supplier-invoices.index') }}" class="btn btn-secondary">Back</a>
    </div>
@endsection
