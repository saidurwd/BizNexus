@extends('adminlte::page')

@section('title', 'Chart of Accounts')

@section('content_header')
    <h1>Chart of Accounts</h1>
    <div class="mt-2">
        <a href="{{ route('finance.accounts.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Account
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Account List</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Normal Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $account)
                        <tr>
                            <td>{{ $account->account_code }}</td>
                            <td>{{ $account->account_name }}</td>
                            <td>
                                <span class="badge bg-{{ $account->account_type === 'ASSET' ? 'primary' : ($account->account_type === 'LIABILITY' ? 'danger' : ($account->account_type === 'REVENUE' ? 'success' : 'warning')) }}">
                                    {{ $account->account_type }}
                                </span>
                            </td>
                            <td>{{ $account->normal_balance }}</td>
                            <td>
                                <span class="badge bg-{{ $account->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ $account->status }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('finance.accounts.show', $account->id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('finance.accounts.edit', $account->id) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No accounts found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
