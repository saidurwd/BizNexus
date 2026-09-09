@extends('layouts.erp')

@section('title', 'Supplier Credit Note')

@section('content_header')
    <h1>Credit Note: {{ $creditNote->credit_note_number }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Credit Note Information</h3>
            <div class="card-tools">
                @if($creditNote->status === 'draft')
                    <a href="{{ route('finance.supplier-credit-notes.edit', $creditNote->id) }}" class="btn btn-sm btn-warning">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <form action="{{ route('finance.supplier-credit-notes.destroy', $creditNote->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this credit note?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </form>
                @endif
            </div>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <tr>
                    <th width="200">Credit Note Number</th>
                    <td>{{ $creditNote->credit_note_number }}</td>
                </tr>
                <tr>
                    <th>Credit Note Date</th>
                    <td>{{ $creditNote->credit_note_date->format('Y-m-d') }}</td>
                </tr>
                <tr>
                    <th>Supplier</th>
                    <td>{{ $creditNote->supplier?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Related Invoice</th>
                    <td>{{ $creditNote->invoice?->invoice_number ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Subtotal</th>
                    <td class="text-right">{{ number_format($creditNote->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <th>Tax Amount</th>
                    <td class="text-right">{{ number_format($creditNote->tax_amount, 2) }}</td>
                </tr>
                <tr>
                    <th>Total Amount</th>
                    <td class="text-right">{{ number_format($creditNote->total_amount, 2) }}</td>
                </tr>
                <tr>
                    <th>Reason</th>
                    <td>{{ $creditNote->reason ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        @php
                            $badgeClass = 'secondary';
                            if ($creditNote->status === 'posted') $badgeClass = 'success';
                            elseif ($creditNote->status === 'approved') $badgeClass = 'info';
                            elseif ($creditNote->status === 'submitted') $badgeClass = 'warning';
                            elseif ($creditNote->status === 'cancelled') $badgeClass = 'danger';
                        @endphp
                        <span class="badge bg-{{ $badgeClass }}">{{ ucfirst($creditNote->status) }}</span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('finance.supplier-credit-notes.index') }}" class="btn btn-secondary">Back</a>

        @if($creditNote->status === 'draft')
            <form action="{{ route('finance.supplier-credit-notes.submit', $creditNote->id) }}" method="POST" class="d-inline ml-2">
                @csrf
                <button type="submit" class="btn btn-success" onclick="return confirm('Submit this credit note for approval?')">
                    <i class="bi bi-send"></i> Submit
                </button>
            </form>
        @endif

        @if($creditNote->status === 'submitted')
            <form action="{{ route('finance.supplier-credit-notes.approve', $creditNote->id) }}" method="POST" class="d-inline ml-2">
                @csrf
                <button type="submit" class="btn btn-success" onclick="return confirm('Approve this credit note?')">
                    <i class="bi bi-check-circle"></i> Approve
                </button>
            </form>
        @endif

        @if($creditNote->status === 'approved')
            <form action="{{ route('finance.supplier-credit-notes.post', $creditNote->id) }}" method="POST" class="d-inline ml-2">
                @csrf
                <button type="submit" class="btn btn-primary" onclick="return confirm('Post this credit note?')">
                    <i class="bi bi-journal-check"></i> Post
                </button>
            </form>
        @endif

        @if(!in_array($creditNote->status, ['posted', 'cancelled']))
            <form action="{{ route('finance.supplier-credit-notes.cancel', $creditNote->id) }}" method="POST" class="d-inline ml-2">
                @csrf
                <button type="submit" class="btn btn-danger" onclick="return confirm('Cancel this credit note?')">
                    <i class="bi bi-x-octagon"></i> Cancel
                </button>
            </form>
        @endif
    </div>
@endsection
