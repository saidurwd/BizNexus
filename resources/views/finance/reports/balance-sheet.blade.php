@extends('adminlte::page')

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
                        @if($account->balance != 0)
                            <div class="d-flex justify-content-between">
                                <span>{{ $account->account_name }}</span>
                                <span>{{ number_format($account->balance, 2) }}</span>
                            </div>
                            @php $totalAssets += $account->balance; @endphp
                        @endif
                    @endforeach
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>Total Assets</strong>
                        <strong>{{ number_format($totalAssets, 2) }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <h4><strong>Liabilities</strong></h4>
                    @php $totalLiabilities = 0; @endphp
                    @foreach($liabilities as $account)
                        @if($account->balance != 0)
                            <div class="d-flex justify-content-between">
                                <span>{{ $account->account_name }}</span>
                                <span>{{ number_format($account->balance, 2) }}</span>
                            </div>
                            @php $totalLiabilities += $account->balance; @endphp
                        @endif
                    @endforeach
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>Total Liabilities</strong>
                        <strong>{{ number_format($totalLiabilities, 2) }}</strong>
                    </div>

                    <h4 class="mt-4"><strong>Equity</strong></h4>
                    @php $totalEquity = 0; @endphp
                    @foreach($equity as $account)
                        @if($account->balance != 0)
                            <div class="d-flex justify-content-between">
                                <span>{{ $account->account_name }}</span>
                                <span>{{ number_format($account->balance, 2) }}</span>
                            </div>
                            @php $totalEquity += $account->balance; @endphp
                        @endif
                    @endforeach
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>Total Equity</strong>
                        <strong>{{ number_format($totalEquity, 2) }}</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>Total Liabilities + Equity</strong>
                        <strong>{{ number_format($totalLiabilities + $totalEquity, 2) }}</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <a href="{{ route('finance.reports.balance-sheet-pdf') }}?as_of_date={{ $asOfDate }}" class="btn btn-sm btn-primary" target="_blank">
                <i class="bi bi-file-pdf"></i> Export PDF
            </a>
        </div>
    </div>
@endsection
