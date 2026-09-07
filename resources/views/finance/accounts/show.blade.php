@extends('adminlte::page')

@section('title', 'Account Details')

@section('content_header')
    <h1>Account: {{ $account->account_name }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Account Information</h3>
            <div class="card-tools">
                <a href="{{ route('finance.accounts.edit', $account->id) }}" class="btn btn-sm btn-warning">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <tr>
                    <th width="200">Account Code</th>
                    <td>{{ $account->account_code }}</td>
                </tr>
                <tr>
                    <th>Account Name</th>
                    <td>{{ $account->account_name }}</td>
                </tr>
                <tr>
                    <th>Account Type</th>
                    <td>{{ $account->account_type }}</td>
                </tr>
                <tr>
                    <th>Normal Balance</th>
                    <td>{{ $account->normal_balance }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="badge bg-{{ $account->status === 'active' ? 'success' : 'secondary' }}">
                            {{ $account->status }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Is Group</th>
                    <td>{{ $account->is_group ? 'Yes' : 'No' }}</td>
                </tr>
                <tr>
                    <th>Is Postable</th>
                    <td>{{ $account->is_postable ? 'Yes' : 'No' }}</td>
                </tr>
                <tr>
                    <th>Current Balance</th>
                    <td><strong>{{ number_format($account->balance, 2) }}</strong></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h3 class="card-title">Recent Journal Lines</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Journal #</th>
                        <th>Description</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($account->journalLines->take(10) as $line)
                        <tr>
                            <td>{{ $line->journal->journal_date->format('Y-m-d') }}</td>
                            <td>{{ $line->journal->journal_number }}</td>
                            <td>{{ $line->description ?? '-' }}</td>
                            <td class="text-right">{{ $line->debit > 0 ? number_format($line->debit, 2) : '-' }}</td>
                            <td class="text-right">{{ $line->credit > 0 ? number_format($line->credit, 2) : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No journal lines found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
