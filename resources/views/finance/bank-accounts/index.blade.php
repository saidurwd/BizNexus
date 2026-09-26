@extends('layouts.erp')

@section('title', __('Bank Accounts - BizNexus'))

@section('content_header')
    <h1>{{ __('Bank Accounts') }}</h1>
    <div class="mt-2">
        <a href="{{ route('finance.bank-accounts.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> {{ __('Add Bank Account') }}
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{{ __('Bank') }}</th>
                        <th>{{ __('Branch') }}</th>
                        <th>{{ __('Account Name') }}</th>
                        <th>{{ __('Account Number') }}</th>
                        <th>{{ __('GL Account') }}</th>
                        <th>{{ __('Currency') }}</th>
                        <th class="text-end">{{ __('Current Balance') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bankAccounts as $account)
                        <tr>
                            <td>{{ $account->bank_name }}</td>
                            <td>{{ $account->branch_name ?? '-' }}</td>
                            <td>{{ $account->account_name }}</td>
                            <td>{{ $account->display_account_number }}</td>
                            <td>{{ $account->glAccount?->account_code ?? '-' }} — {{ $account->glAccount?->account_name ?? '-' }}</td>
                            <td>{{ $account->currency?->code ?? '-' }}</td>
                            <td class="text-end">{{ Formatter::amount($account->current_balance) }}</td>
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
                            <td colspan="9" class="text-center">{{ __('No bank accounts found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
