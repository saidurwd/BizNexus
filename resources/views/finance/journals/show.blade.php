@extends('layouts.erp')

@section('title', __('Journal Entry Details'))

@section('content_header')
    <h1>{{ __('Journal :number', ['number' => $journal->journal_number]) }}</h1>
@endsection

@section('content')
    <x-print-toolbar />
    <x-print-document-header :title="__('Journal voucher')" :subtitle="$journal->journal_number" />

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Journal Information') }}</h3>
            <div class="card-tools">
                @if($journal->status === 'DRAFT')
                    <form action="{{ route('finance.journals.destroy', $journal->id) }}" method="POST" class="d-inline" onsubmit="return confirm(@js(__('Are you sure?')))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="bi bi-trash"></i> {{ __('Delete') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <tr>
                    <th width="200">{{ __('Journal Number') }}</th>
                    <td>{{ $journal->journal_number }}</td>
                </tr>
                <tr>
                    <th>{{ __('Journal Date') }}</th>
                    <td>{{ $journal->journal_date->format('Y-m-d') }}</td>
                </tr>
                <tr>
                    <th>{{ __('Branch') }}</th>
                    <td>{{ $journal->branch?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Posting Date') }}</th>
                    <td>{{ $journal->posting_date?->format('Y-m-d H:i:s') ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Fiscal Period') }}</th>
                    <td>{{ $journal->fiscalPeriod?->period_name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Description') }}</th>
                    <td>{{ $journal->description ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Status') }}</th>
                    <td>
                        <span class="badge bg-{{ $journal->status === 'POSTED' ? 'success' : ($journal->status === 'DRAFT' ? 'secondary' : ($journal->status === 'APPROVED' ? 'info' : ($journal->status === 'REJECTED' ? 'danger' : 'warning'))) }}">
                            {{ $journal->status }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>{{ __('Created By') }}</th>
                    <td>{{ $journal->createdBy?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Posted By') }}</th>
                    <td>{{ $journal->postedBy?->name ?? '-' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h3 class="card-title">{{ __('Journal Lines') }}</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>{{ __('Account Code') }}</th>
                        <th>{{ __('Account Name') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th class="text-end">{{ __('Debit') }}</th>
                        <th class="text-end">{{ __('Credit') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($journal->lines as $line)
                        <tr>
                            <td>{{ $line->account->account_code }}</td>
                            <td>{{ $line->account->account_name }}</td>
                            <td>{{ $line->description ?? '-' }}</td>
                            <td class="text-end">{{ $line->debit > 0 ? Formatter::amount($line->debit) : '-' }}</td>
                            <td class="text-end">{{ $line->credit > 0 ? Formatter::amount($line->credit) : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end"><strong>{{ __('TOTALS') }}</strong></td>
                        <td class="text-end"><strong>{{ Formatter::amount($journal->total_debit) }}</strong></td>
                        <td class="text-end"><strong>{{ Formatter::amount($journal->total_credit) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if($journal->status === 'DRAFT')
        <div class="mt-4">
            @if($journal->canSubmit())
                <form action="{{ route('finance.journals.submit', $journal->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-send"></i> {{ __('Submit for Approval') }}
                    </button>
                </form>
            @else
                <button type="button" class="btn btn-secondary" disabled title="{{ __('Journal must have at least 2 balanced lines to be submitted') }}">
                    <i class="bi bi-send"></i> {{ __('Submit for Approval') }}
                </button>
                <small class="text-muted d-block mt-1">{{ __('Journal must have at least 2 balanced lines to be submitted.') }}</small>
            @endif
            <a href="{{ route('finance.journals.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> {{ __('Back to Journals') }}
            </a>
        </div>
    @elseif($journal->status === 'SUBMITTED')
        <div class="mt-4">
            <form action="{{ route('finance.journals.approve', $journal->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check"></i> {{ __('Approve') }}
                </button>
            </form>
            <form action="{{ route('finance.journals.reject', $journal->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-x"></i> {{ __('Reject') }}
                </button>
            </form>
            <a href="{{ route('finance.journals.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> {{ __('Back to Journals') }}
            </a>
        </div>
    @elseif($journal->status === 'APPROVED')
        <div class="mt-4">
            <form action="{{ route('finance.journals.post', $journal->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> {{ __('Post Journal') }}
                </button>
            </form>
            <a href="{{ route('finance.journals.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> {{ __('Back to Journals') }}
            </a>
        </div>
    @elseif($journal->status === 'POSTED')
        <div class="mt-4">
            <form action="{{ route('finance.journals.reverse', $journal->id) }}" method="POST" class="d-inline" onsubmit="return confirm(@js(__('Are you sure you want to reverse this journal?')))">
                @csrf
                <button type="submit" class="btn btn-warning">
                    <i class="bi bi-arrow-counterclockwise"></i> {{ __('Reverse') }}
                </button>
            </form>
            <a href="{{ route('finance.journals.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> {{ __('Back to Journals') }}
            </a>
        </div>
    @else
        <div class="mt-4">
            <a href="{{ route('finance.journals.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> {{ __('Back to Journals') }}
            </a>
        </div>
    @endif
    <x-attachments :document="$journal" type="journals" />
    <x-print-signatures />
@endsection
