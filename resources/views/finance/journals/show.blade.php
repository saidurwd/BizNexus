@extends('layouts.erp')

@section('title', 'Journal Entry Details')

@section('content_header')
    <h1>Journal Entry: {{ $journal->journal_number }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Journal Information</h3>
            <div class="card-tools">
                @if($journal->status === 'DRAFT')
                    <form action="{{ route('finance.journals.destroy', $journal->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </form>
                @endif
            </div>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <tr>
                    <th width="200">Journal Number</th>
                    <td>{{ $journal->journal_number }}</td>
                </tr>
                <tr>
                    <th>Journal Date</th>
                    <td>{{ $journal->journal_date->format('Y-m-d') }}</td>
                </tr>
                <tr>
                    <th>Branch</th>
                    <td>{{ $journal->branch?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Posting Date</th>
                    <td>{{ $journal->posting_date?->format('Y-m-d H:i:s') ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Fiscal Period</th>
                    <td>{{ $journal->fiscalPeriod?->period_name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Description</th>
                    <td>{{ $journal->description ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="badge bg-{{ $journal->status === 'POSTED' ? 'success' : ($journal->status === 'DRAFT' ? 'secondary' : ($journal->status === 'APPROVED' ? 'info' : ($journal->status === 'REJECTED' ? 'danger' : 'warning'))) }}">
                            {{ $journal->status }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Created By</th>
                    <td>{{ $journal->createdBy?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Posted By</th>
                    <td>{{ $journal->postedBy?->name ?? '-' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h3 class="card-title">Journal Lines</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Account Code</th>
                        <th>Account Name</th>
                        <th>Description</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($journal->lines as $line)
                        <tr>
                            <td>{{ $line->account->account_code }}</td>
                            <td>{{ $line->account->account_name }}</td>
                            <td>{{ $line->description ?? '-' }}</td>
                            <td class="text-right">{{ $line->debit > 0 ? number_format($line->debit, 2) : '-' }}</td>
                            <td class="text-right">{{ $line->credit > 0 ? number_format($line->credit, 2) : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right"><strong>TOTALS</strong></td>
                        <td class="text-right"><strong>{{ number_format($journal->total_debit, 2) }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($journal->total_credit, 2) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if($journal->status === 'DRAFT')
        <div class="mt-4">
            <form action="{{ route('finance.journals.submit', $journal->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-warning">
                    <i class="bi bi-send"></i> Submit for Approval
                </button>
            </form>
            <a href="{{ route('finance.journals.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Journals
            </a>
        </div>
    @elseif($journal->status === 'SUBMITTED')
        <div class="mt-4">
            <form action="{{ route('finance.journals.approve', $journal->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check"></i> Approve
                </button>
            </form>
            <form action="{{ route('finance.journals.reject', $journal->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-x"></i> Reject
                </button>
            </form>
            <a href="{{ route('finance.journals.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Journals
            </a>
        </div>
    @elseif($journal->status === 'APPROVED')
        <div class="mt-4">
            <form action="{{ route('finance.journals.post', $journal->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> Post Journal
                </button>
            </form>
            <a href="{{ route('finance.journals.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Journals
            </a>
        </div>
    @elseif($journal->status === 'POSTED')
        <div class="mt-4">
            <form action="{{ route('finance.journals.reverse', $journal->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reverse this journal?')">
                @csrf
                <button type="submit" class="btn btn-warning">
                    <i class="bi bi-arrow-counterclockwise"></i> Reverse
                </button>
            </form>
            <a href="{{ route('finance.journals.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Journals
            </a>
        </div>
    @else
        <div class="mt-4">
            <a href="{{ route('finance.journals.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Journals
            </a>
        </div>
    @endif
@endsection
