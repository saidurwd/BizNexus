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
    <x-finance.list-filters :filters="$filters" :statuses="['DRAFT', 'SUBMITTED', 'APPROVED', 'POSTED', 'REVERSED', 'REJECTED', 'CANCELLED']" :search-label="__('Journal number or description')" />
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Journal Register</h3>
            @if($company)
                <span class="text-muted ms-2">({{ $company->code }} — {{ $company->name }})</span>
            @endif
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Journal #</th>
                        <th>Description</th>
                        <th>Period</th>
                        <th class="text-end">Total Debit</th>
                        <th class="text-end">Total Credit</th>
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
                            <td class="text-end">{{ Formatter::amount($journal->total_debit) }}</td>
                            <td class="text-end">{{ Formatter::amount($journal->total_credit) }}</td>
                            <td>
                                <span class="badge bg-{{ $journal->status === 'POSTED' ? 'success' : ($journal->status === 'DRAFT' ? 'secondary' : ($journal->status === 'APPROVED' ? 'info' : ($journal->status === 'REJECTED' ? 'danger' : ($journal->status === 'CANCELLED' ? 'dark' : 'warning')))) }}">
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
                            <td colspan="8" class="text-center">
                                No journals found for {{ $company?->name ?? 'this company' }}.
                                @if($company && \Modules\Finance\Models\Journal::where('company_id', $company->id)->count() == 0)
                                    <br><small class="text-muted">You can <a href="{{ route('finance.journals.create') }}">create a new journal entry</a> or switch to a different company.</small>
                                @endif
                            </td>
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
