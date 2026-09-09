@extends('layouts.erp')

@section('title', 'Supplier Invoices')

@section('content_header')
    <h1>Supplier Invoices</h1>
    <div class="mt-2">
        <a href="{{ route('finance.supplier-invoices.create') }}" class="btn btn-primary">
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
                        <th>Supplier</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">Tax</th>
                        <th class="text-right">Total</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_number }}</td>
                            <td>{{ $invoice->invoice_date->format('Y-m-d') }}</td>
                            <td>{{ $invoice->supplier?->name ?? '-' }}</td>
                            <td class="text-right">{{ number_format($invoice->subtotal, 2) }}</td>
                            <td class="text-right">{{ number_format($invoice->tax_amount, 2) }}</td>
                            <td class="text-right">{{ number_format($invoice->total_amount, 2) }}</td>
                            <td>
                                @php
                                    $badgeClass = 'secondary';
                                    if ($invoice->status === 'PAID') $badgeClass = 'success';
                                    elseif ($invoice->status === 'APPROVED') $badgeClass = 'info';
                                    elseif ($invoice->status === 'POSTED') $badgeClass = 'primary';
                                    elseif ($invoice->status === 'SUBMITTED') $badgeClass = 'warning';
                                    elseif ($invoice->status === 'REJECTED') $badgeClass = 'danger';
                                    elseif ($invoice->status === 'CANCELLED') $badgeClass = 'dark';
                                @endphp
                                <span class="badge bg-{{ $badgeClass }}">{{ $invoice->status }}</span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('finance.supplier-invoices.show', $invoice->id) }}" class="btn btn-sm btn-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($invoice->isDraft())
                                    <a href="{{ route('finance.supplier-invoices.edit', $invoice->id) }}" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('finance.supplier-invoices.destroy', $invoice->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this invoice?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                                @if($invoice->isDraft())
                                    <form action="{{ route('finance.supplier-invoices.submit', $invoice->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Submit this invoice?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="Submit">
                                            <i class="bi bi-send"></i>
                                        </button>
                                    </form>
                                @endif
                                @if($invoice->isSubmitted())
                                    <form action="{{ route('finance.supplier-invoices.approve', $invoice->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Approve this invoice?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('finance.supplier-invoices.reject', $invoice->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Reject this invoice?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger" title="Reject">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </form>
                                @endif
                                @if($invoice->isApproved() || $invoice->isSubmitted())
                                    <form action="{{ route('finance.supplier-invoices.post', $invoice->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Post this invoice?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary" title="Post">
                                            <i class="bi bi-journal-check"></i>
                                        </button>
                                    </form>
                                @endif
                                @if(!$invoice->isPosted() && !$invoice->isPaid())
                                    <form action="{{ route('finance.supplier-invoices.cancel', $invoice->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel this invoice?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-dark" title="Cancel">
                                            <i class="bi bi-x-octagon"></i>
                                        </button>
                                    </form>
                                @endif
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
