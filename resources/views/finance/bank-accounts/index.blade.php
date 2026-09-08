@extends('layouts.erp')

@section('title', 'Bank Accounts - BizNexus')

@section('content_header')
    <h1>Bank Accounts</h1>
    <div class="mt-2">
        <a href="{{ route('finance.bank-accounts.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Bank Account
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Bank</th>
                        <th>Branch</th>
                        <th>Account Name</th>
                        <th>Account Number</th>
                        <th>GL Account</th>
                        <th>Currency</th>
                        <th class="text-right">Current Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bankAccounts as $account)
                        <tr>
                            <td>{{ $account->bank_name }}</td>
                            <td>{{ $account->branch_name ?? '-' }}</td>
                            <td>{{ $account->account_name }}</td>
                            <td>{{ $account->account_number }}</td>
                            <td>{{ $account->glAccount?->account_code ?? '-' }} — {{ $account->glAccount?->account_name ?? '-' }}</td>
                            <td>{{ $account->currency?->code ?? '-' }}</td>
                            <td class="text-right">{{ number_format($account->current_balance, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $account->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ $account->status }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('finance.bank-accounts.edit', $account->id) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('finance.bank-accounts.destroy', $account->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete {{ addslashes($account->bank_name . ' - ' . $account->account_name) }}? This cannot be undone.')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">No bank accounts found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
