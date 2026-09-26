@extends('layouts.erp')

@section('title', 'Balance Sheet')

@section('content_header')
    <h1>Balance Sheet</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Balance Sheet as of {{ $asOfDate }}</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h4><strong>Assets</strong></h4>
                    @php $totalAssets = 0; @endphp
                    @foreach($assets as $account)
                        @if($account['amount'] != 0)
                            <div class="d-flex justify-content-between">
                                <span>{{ $account['account_name'] }}</span>
                                <span>{{ Formatter::amount($account['amount']) }}</span>
                            </div>
                            @php $totalAssets += $account['amount']; @endphp
                        @endif
                    @endforeach
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>Total Assets</strong>
                        <strong>{{ Formatter::amount($totalAssets) }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <h4><strong>Liabilities</strong></h4>
                    @php $totalLiabilities = 0; @endphp
                    @foreach($liabilities as $account)
                        @if($account['amount'] != 0)
                            <div class="d-flex justify-content-between">
                                <span>{{ $account['account_name'] }}</span>
                                <span>{{ Formatter::amount($account['amount']) }}</span>
                            </div>
                            @php $totalLiabilities += $account['amount']; @endphp
                        @endif
                    @endforeach
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>Total Liabilities</strong>
                        <strong>{{ Formatter::amount($totalLiabilities) }}</strong>
                    </div>

                    <h4 class="mt-4"><strong>Equity</strong></h4>
                    @php $totalEquity = 0; @endphp
                    @foreach($equity as $account)
                        @if($account['amount'] != 0)
                            <div class="d-flex justify-content-between">
                                <span>{{ $account['account_name'] }}</span>
                                <span>{{ Formatter::amount($account['amount']) }}</span>
                            </div>
                            @php $totalEquity += $account['amount']; @endphp
                        @endif
                    @endforeach
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>Total Equity</strong>
                        <strong>{{ Formatter::amount($totalEquity) }}</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>Total Liabilities + Equity</strong>
                        <strong>{{ Formatter::amount($totalLiabilities + $totalEquity) }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
