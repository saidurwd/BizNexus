@extends('adminlte::page')

@section('title', 'Cash Accounts - BizNexus')

@section('content_header')
    <h1>Cash Accounts</h1>
    <div class="mt-2">
        <a href="{{ route('finance.cash-accounts.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Cash Account
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>GL Account</th>
                        <th>Currency</th>
                        <th>Opening Balance</th>
                        <th>Current Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cashAccounts as $account)
                        <tr>
                            <td>{{ $account->code }}</td>
                            <td>{{ $account->name }}</td>
                            <td>{{ $account->account_type }}</td>
                            <td>{{ $account->glAccount?->account_code ?? '-' }} — {{ $account->glAccount?->account_name ?? '-' }}</td>
                            <td>{{ $account->currency_code }}</td>
                            <td class="text-right">{{ number_format($account->opening_balance, 2) }}</td>
                            <td class="text-right">{{ number_format($account->current_balance, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $account->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ $account->status }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('finance.cash-accounts.edit', $account->id) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('finance.cash-accounts.destroy', $account->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete {{ addslashes($account->name) }}? This cannot be undone.')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">No cash accounts found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
