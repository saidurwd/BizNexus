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
            @if($company)
                <span class="text-muted ml-2">({{ $company->code }} — {{ $company->name }})</span>
            @endif
            <div class="card-tools">
                <form method="GET" action="{{ route('finance.journals.index') }}" class="form-inline">
                    <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="DRAFT" {{ request('status') === 'DRAFT' ? 'selected' : '' }}>Draft</option>
                        <option value="SUBMITTED" {{ request('status') === 'SUBMITTED' ? 'selected' : '' }}>Submitted</option>
                        <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>Approved</option>
                        <option value="POSTED" {{ request('status') === 'POSTED' ? 'selected' : '' }}>Posted</option>
                        <option value="REJECTED" {{ request('status') === 'REJECTED' ? 'selected' : '' }}>Rejected</option>
                        <option value="CANCELLED" {{ request('status') === 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </form>
            </div>
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
