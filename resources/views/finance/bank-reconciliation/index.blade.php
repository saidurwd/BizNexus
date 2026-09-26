@extends('layouts.erp')

@section('title', __('Bank Reconciliation - BizNexus'))

@section('content_header')
    <h1>{{ __('Bank Reconciliation') }}</h1>
    <div class="mt-2">
        <a href="{{ route('finance.bank-reconciliation.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> {{ __('New Reconciliation') }}
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{{ __('Bank Account') }}</th>
                        <th>{{ __('Statement Date') }}</th>
                        <th class="text-end">{{ __('Statement Balance') }}</th>
                        <th class="text-end">{{ __('Book Balance') }}</th>
                        <th class="text-end">{{ __('Difference') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Reconciled By') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reconciliations as $recon)
                        <tr>
                            <td>{{ $recon->bankAccount?->bank_name ?? '-' }} — {{ $recon->bankAccount?->display_account_number ?? '-' }}</td>
                            <td>{{ $recon->statement_date->format('Y-m-d') }}</td>
                            <td class="text-end">{{ Formatter::amount($recon->statement_balance) }}</td>
                            <td class="text-end">{{ Formatter::amount($recon->book_balance) }}</td>
                            <td class="text-end">{{ Formatter::amount($recon->difference) }}</td>
                            <td>
                                <span class="badge bg-{{ $recon->status === 'RECONCILED' ? 'success' : ($recon->status === 'PENDING' ? 'warning' : 'danger') }}">
                                    {{ $recon->status }}
                                </span>
                            </td>
                            <td>{{ $recon->reconciledBy?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">{{ __('No reconciliations found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
