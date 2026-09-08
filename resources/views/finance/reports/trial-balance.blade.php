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
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalDebit = 0; $totalCredit = 0; @endphp
                    @foreach($accounts as $account)
                        @if($account['debit'] != 0 || $account['credit'] != 0)
                            <tr>
                                <td>{{ $account['account_code'] }}</td>
                                <td>{{ $account['account_name'] }}</td>
                                <td class="text-right">{{ $account['debit'] > 0 ? number_format($account['debit'], 2) : '-' }}</td>
                                <td class="text-right">{{ $account['credit'] > 0 ? number_format($account['credit'], 2) : '-' }}</td>
                            </tr>
                            @php 
                                $totalDebit += $account['debit'];
                                $totalCredit += $account['credit'];
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
    </div>
@endsection
