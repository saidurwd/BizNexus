@extends('layouts.erp')

@section('title', 'Supplier Debit Note')

@section('content_header')
    <h1>Debit Note: {{ $debitNote->note_number }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Debit Note Information</h3>
            <div class="card-tools">
                @if($debitNote->isDraft())
                    <a href="{{ route('finance.supplier-debit-notes.edit', $debitNote->id) }}" class="btn btn-sm btn-warning">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <form action="{{ route('finance.supplier-debit-notes.destroy', $debitNote->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this debit note?')">
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
                    <th width="200">Debit Note Number</th>
                    <td>{{ $debitNote->note_number }}</td>
                </tr>
                <tr>
                    <th>Debit Note Date</th>
                    <td>{{ $debitNote->note_date->format('Y-m-d') }}</td>
                </tr>
                <tr>
                    <th>Supplier</th>
                    <td>{{ $debitNote->supplier?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Reference Type</th>
                    <td>{{ $debitNote->reference_type ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Reference ID</th>
                    <td>{{ $debitNote->reference_id ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Amount</th>
                    <td class="text-right">{{ number_format($debitNote->amount, 2) }}</td>
                </tr>
                <tr>
                    <th>Description</th>
                    <td>{{ $debitNote->description ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        @php
                            $badgeClass = 'secondary';
                            if ($debitNote->status === 'POSTED') $badgeClass = 'success';
                            elseif ($debitNote->status === 'CANCELLED') $badgeClass = 'danger';
                        @endphp
                        <span class="badge bg-{{ $badgeClass }}">{{ $debitNote->status }}</span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('finance.supplier-debit-notes.index') }}" class="btn btn-secondary">Back</a>

        @if($debitNote->isDraft())
            <form action="{{ route('finance.supplier-debit-notes.post', $debitNote->id) }}" method="POST" class="d-inline ml-2">
                @csrf
                <button type="submit" class="btn btn-primary" onclick="return confirm('Post this debit note?')">
                    <i class="bi bi-journal-check"></i> Post
                </button>
            </form>
        @endif

        @if($debitNote->isDraft())
            <form action="{{ route('finance.supplier-debit-notes.cancel', $debitNote->id) }}" method="POST" class="d-inline ml-2">
                @csrf
                <button type="submit" class="btn btn-danger" onclick="return confirm('Cancel this debit note?')">
                    <i class="bi bi-x-octagon"></i> Cancel
                </button>
            </form>
        @endif
    </div>
@endsection
