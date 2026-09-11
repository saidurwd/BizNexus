@extends('layouts.erp')

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
                        <th class="text-right">Opening Debit</th>
                        <th class="text-right">Opening Credit</th>
                        <th class="text-right">Period Debit</th>
                        <th class="text-right">Period Credit</th>
                        <th class="text-right">Closing Debit</th>
                        <th class="text-right">Closing Credit</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $totalOpeningDebit = 0; 
                        $totalOpeningCredit = 0; 
                        $totalPeriodDebit = 0; 
                        $totalPeriodCredit = 0; 
                        $totalClosingDebit = 0; 
                        $totalClosingCredit = 0; 
                    @endphp
                    @foreach($accounts as $account)
                        @if($account['period_debit'] != 0 || $account['period_credit'] != 0 || $account['opening_debit'] != 0 || $account['opening_credit'] != 0)
                            <tr>
                                <td>{{ $account['account_code'] }}</td>
                                <td>{{ $account['account_name'] }}</td>
                                <td class="text-right">{{ $account['opening_debit'] > 0 ? number_format($account['opening_debit'], 2) : '-' }}</td>
                                <td class="text-right">{{ $account['opening_credit'] > 0 ? number_format($account['opening_credit'], 2) : '-' }}</td>
                                <td class="text-right">{{ $account['period_debit'] > 0 ? number_format($account['period_debit'], 2) : '-' }}</td>
                                <td class="text-right">{{ $account['period_credit'] > 0 ? number_format($account['period_credit'], 2) : '-' }}</td>
                                <td class="text-right">{{ $account['closing_debit'] > 0 ? number_format($account['closing_debit'], 2) : '-' }}</td>
                                <td class="text-right">{{ $account['closing_credit'] > 0 ? number_format($account['closing_credit'], 2) : '-' }}</td>
                            </tr>
                            @php 
                                $totalOpeningDebit += $account['opening_debit'];
                                $totalOpeningCredit += $account['opening_credit'];
                                $totalPeriodDebit += $account['period_debit'];
                                $totalPeriodCredit += $account['period_credit'];
                                $totalClosingDebit += $account['closing_debit'];
                                $totalClosingCredit += $account['closing_credit'];
                            @endphp
                        @endif
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-secondary">
                        <td colspan="2" class="text-right"><strong>TOTALS</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalOpeningDebit, 2) }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalOpeningCredit, 2) }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalPeriodDebit, 2) }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalPeriodCredit, 2) }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalClosingDebit, 2) }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalClosingCredit, 2) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
