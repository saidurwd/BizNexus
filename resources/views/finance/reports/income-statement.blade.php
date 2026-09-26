@extends('layouts.erp')

@section('title', 'Income Statement')

@section('content_header')
    <h1>Income Statement</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Income Statement for period {{ $startDate }} to {{ $endDate }}</h3>
        </div>
        <div class="card-body">
            <h4><strong>Revenue</strong></h4>
            @php $totalRevenue = 0; @endphp
            @foreach($revenue as $account)
                @if($account['amount'] != 0)
                    <div class="d-flex justify-content-between">
                        <span>{{ $account['account_name'] }}</span>
                        <span>{{ Formatter::amount($account['amount']) }}</span>
                    </div>
                    @php $totalRevenue += $account['amount']; @endphp
                @endif
            @endforeach
            <hr>
            <div class="d-flex justify-content-between">
                <strong>Total Revenue</strong>
                <strong>{{ Formatter::amount($totalRevenue) }}</strong>
            </div>

            <h4 class="mt-4"><strong>Expenses</strong></h4>
            @php $totalExpenses = 0; @endphp
            @foreach($expenses as $account)
                @if($account['amount'] != 0)
                    <div class="d-flex justify-content-between">
                        <span>{{ $account['account_name'] }}</span>
                        <span>{{ Formatter::amount($account['amount']) }}</span>
                    </div>
                    @php $totalExpenses += $account['amount']; @endphp
                @endif
            @endforeach
            <hr>
            <div class="d-flex justify-content-between">
                <strong>Total Expenses</strong>
                <strong>{{ Formatter::amount($totalExpenses) }}</strong>
            </div>

            <hr>
            <div class="d-flex justify-content-between">
                <h4><strong>Net Income</strong></h4>
                <h4><strong>{{ Formatter::amount($totalRevenue - $totalExpenses) }}</strong></h4>
            </div>
        </div>
    </div>
@endsection
