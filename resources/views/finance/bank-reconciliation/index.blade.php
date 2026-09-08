@extends('layouts.erp')

@section('title', 'Bank Reconciliation - BizNexus')

@section('content_header')
    <h1>Bank Reconciliation</h1>
    <div class="mt-2">
        <a href="{{ route('finance.bank-reconciliation.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Reconciliation
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Bank Account</th>
                        <th>Statement Date</th>
                        <th class="text-right">Statement Balance</th>
                        <th class="text-right">Book Balance</th>
                        <th class="text-right">Difference</th>
                        <th>Status</th>
                        <th>Reconciled By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reconciliations as $recon)
                        <tr>
                            <td>{{ $recon->bankAccount?->bank_name ?? '-' }} — {{ $recon->bankAccount?->account_number ?? '-' }}</td>
                            <td>{{ $recon->statement_date->format('Y-m-d') }}</td>
                            <td class="text-right">{{ number_format($recon->statement_balance, 2) }}</td>
                            <td class="text-right">{{ number_format($recon->book_balance, 2) }}</td>
                            <td class="text-right">{{ number_format($recon->difference, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $recon->status === 'RECONCILED' ? 'success' : ($recon->status === 'PENDING' ? 'warning' : 'danger') }}">
                                    {{ $recon->status }}
                                </span>
                            </td>
                            <td>{{ $recon->reconciledBy?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No reconciliations found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
