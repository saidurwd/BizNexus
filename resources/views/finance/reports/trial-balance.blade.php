@extends('adminlte::page')

@section('title', 'Trial Balance')

@section('content_header')
    <h1>Trial Balance</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Trial Balance as of {{ $asOfDate }}</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Account Code</th>
                        <th>Account Name</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalDebit = 0; $totalCredit = 0; @endphp
                    @foreach($accounts as $account)
                        @if($account->debit_balance != 0 || $account->credit_balance != 0)
                            <tr>
                                <td>{{ $account->account_code }}</td>
                                <td>{{ $account->account_name }}</td>
                                <td class="text-right">{{ $account->debit_balance > 0 ? number_format($account->debit_balance, 2) : '-' }}</td>
                                <td class="text-right">{{ $account->credit_balance > 0 ? number_format($account->credit_balance, 2) : '-' }}</td>
                            </tr>
                            @php 
                                $totalDebit += $account->debit_balance;
                                $totalCredit += $account->credit_balance;
                            @endphp
                        @endif
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-secondary">
                        <td colspan="2" class="text-right"><strong>TOTALS</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalDebit, 2) }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalCredit, 2) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="card-footer">
            <a href="{{ route('finance.reports.trial-balance-pdf') }}?as_of_date={{ $asOfDate }}" class="btn btn-sm btn-primary" target="_blank">
                <i class="bi bi-file-pdf"></i> Export PDF
            </a>
        </div>
    </div>
@endsection
