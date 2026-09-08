@extends('layouts.erp')

@section('title', 'Journal Entries')

@section('content_header')
    <h1>Journal Entries</h1>
    <div class="mt-2">
        <a href="{{ route('finance.journals.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Journal Entry
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Journal Register</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Journal #</th>
                        <th>Description</th>
                        <th>Period</th>
                        <th class="text-right">Total Debit</th>
                        <th class="text-right">Total Credit</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($journals as $journal)
                        <tr>
                            <td>{{ $journal->journal_date->format('Y-m-d') }}</td>
                            <td>{{ $journal->journal_number }}</td>
                            <td>{{ $journal->description ?: '-' }}</td>
                            <td>{{ $journal->fiscalPeriod?->period_name ?? '-' }}</td>
                            <td class="text-right">{{ number_format($journal->total_debit, 2) }}</td>
                            <td class="text-right">{{ number_format($journal->total_credit, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $journal->status === 'POSTED' ? 'success' : ($journal->status === 'DRAFT' ? 'secondary' : 'warning') }}">
                                    {{ $journal->status }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('finance.journals.show', $journal->id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No journals found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $journals->links() }}
        </div>
    </div>
@endsection
