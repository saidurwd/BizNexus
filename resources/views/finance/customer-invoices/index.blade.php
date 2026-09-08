@extends('layouts.erp')

@section('title', 'Customer Invoices')

@section('content_header')
    <h1>Customer Invoices</h1>
    <div class="mt-2">
        <a href="{{ route('finance.customer-invoices.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Invoice
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">Tax</th>
                        <th class="text-right">Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_number }}</td>
                            <td>{{ $invoice->invoice_date->format('Y-m-d') }}</td>
                            <td>{{ $invoice->customer?->name ?? '-' }}</td>
                            <td class="text-right">{{ number_format($invoice->subtotal, 2) }}</td>
                            <td class="text-right">{{ number_format($invoice->tax_amount, 2) }}</td>
                            <td class="text-right">{{ number_format($invoice->total_amount, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $invoice->status === 'PAID' ? 'success' : ($invoice->status === 'APPROVED' ? 'info' : ($invoice->status === 'DRAFT' ? 'secondary' : 'warning')) }}">
                                    {{ $invoice->status }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('finance.customer-invoices.show', $invoice->id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No invoices found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
