@extends('layouts.erp')

@section('title', __('Account Details'))

@section('content_header')
    <h1>{{ __('Account: :name', ['name' => $account->account_name]) }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Account Information') }}</h3>
            <div class="card-tools">
                <a href="{{ route('finance.accounts.edit', $account->id) }}" class="btn btn-sm btn-warning">
                    <i class="bi bi-pencil"></i> {{ __('Edit') }}
                </a>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <tr>
                    <th width="200">{{ __('Account Code') }}</th>
                    <td>{{ $account->account_code }}</td>
                </tr>
                <tr>
                    <th>{{ __('Account Name') }}</th>
                    <td>{{ $account->account_name }}</td>
                </tr>
                <tr>
                    <th>{{ __('Account Type') }}</th>
                    <td>{{ $account->account_type }}</td>
                </tr>
                <tr>
                    <th>{{ __('Account Category') }}</th>
                    <td>{{ $account->category?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Normal Balance') }}</th>
                    <td>{{ $account->normal_balance }}</td>
                </tr>
                <tr>
                    <th>{{ __('Status') }}</th>
                    <td>
                        <span class="badge bg-{{ $account->status === 'active' ? 'success' : 'secondary' }}">
                            {{ $account->status }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>{{ __('Is Group') }}</th>
                    <td>{{ $account->is_group ? 'Yes' : 'No' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Is Postable') }}</th>
                    <td>{{ $account->is_postable ? 'Yes' : 'No' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Current Balance') }}</th>
                    <td><strong>{{ Formatter::amount($account->balance) }}</strong></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h3 class="card-title">{{ __('Recent Journal Lines') }}</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>Journal #</th>
                        <th>{{ __('Description') }}</th>
                        <th class="text-end">{{ __('Debit') }}</th>
                        <th class="text-end">{{ __('Credit') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($account->journalLines->take(10) as $line)
                        <tr>
                            <td>{{ $line->journal->journal_date->format('Y-m-d') }}</td>
                            <td>{{ $line->journal->journal_number }}</td>
                            <td>{{ $line->description ?? '-' }}</td>
                            <td class="text-end">{{ $line->debit > 0 ? Formatter::amount($line->debit) : '-' }}</td>
                            <td class="text-end">{{ $line->credit > 0 ? Formatter::amount($line->credit) : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">{{ __('No journal lines found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
