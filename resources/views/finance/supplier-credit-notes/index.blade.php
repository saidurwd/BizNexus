@extends('layouts.erp')

@section('title', 'Supplier Credit Notes')

@section('content_header')
    <h1>Supplier Credit Notes</h1>
    <div class="mt-2">
        <a href="{{ route('finance.supplier-credit-notes.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Credit Note
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Credit Note #</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Related Invoice</th>
                        <th class="text-right">Total Amount</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($creditNotes as $note)
                        <tr>
                            <td>{{ $note->credit_note_number }}</td>
                            <td>{{ $note->credit_note_date->format('Y-m-d') }}</td>
                            <td>{{ $note->supplier?->name ?? '-' }}</td>
                            <td>{{ $note->invoice?->invoice_number ?? '-' }}</td>
                            <td class="text-right">{{ number_format($note->total_amount, 2) }}</td>
                            <td>
                                @php
                                    $badgeClass = 'secondary';
                                    if ($note->status === 'posted') $badgeClass = 'success';
                                    elseif ($note->status === 'approved') $badgeClass = 'info';
                                    elseif ($note->status === 'submitted') $badgeClass = 'warning';
                                    elseif ($note->status === 'cancelled') $badgeClass = 'danger';
                                @endphp
                                <span class="badge bg-{{ $badgeClass }}">{{ ucfirst($note->status) }}</span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('finance.supplier-credit-notes.show', $note->id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($note->isDraft())
                                    <a href="{{ route('finance.supplier-credit-notes.edit', $note->id) }}" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('finance.supplier-credit-notes.destroy', $note->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this credit note?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No credit notes found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
