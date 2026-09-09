@extends('layouts.erp')

@section('title', 'Supplier Debit Notes')

@section('content_header')
    <h1>Supplier Debit Notes</h1>
    <div class="mt-2">
        <a href="{{ route('finance.supplier-debit-notes.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Debit Note
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Debit Note #</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th class="text-right">Amount</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($debitNotes as $note)
                        <tr>
                            <td>{{ $note->note_number }}</td>
                            <td>{{ $note->note_date->format('Y-m-d') }}</td>
                            <td>{{ $note->supplier?->name ?? '-' }}</td>
                            <td class="text-right">{{ number_format($note->amount, 2) }}</td>
                            <td>
                                @php
                                    $badgeClass = 'secondary';
                                    if ($note->status === 'POSTED') $badgeClass = 'success';
                                    elseif ($note->status === 'CANCELLED') $badgeClass = 'danger';
                                @endphp
                                <span class="badge bg-{{ $badgeClass }}">{{ $note->status }}</span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('finance.supplier-debit-notes.show', $note->id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($note->isDraft())
                                    <a href="{{ route('finance.supplier-debit-notes.edit', $note->id) }}" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('finance.supplier-debit-notes.destroy', $note->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this debit note?')">
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
                            <td colspan="6" class="text-center">No debit notes found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
