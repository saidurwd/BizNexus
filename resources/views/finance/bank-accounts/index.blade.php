@extends('adminlte::page')

@section('title', 'Bank Accounts')

@section('content_header')
    <h1>Bank Accounts</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Bank Account Register</h3>
        </div>
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No bank accounts found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
