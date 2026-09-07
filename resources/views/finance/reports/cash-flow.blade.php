@extends('adminlte::page')

@section('title', 'Cash Flow Statement')

@section('content_header')
    <h1>Cash Flow Statement</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Cash Flow for period {{ $startDate }} to {{ $endDate }}</h3>
        </div>
        <div class="card-body">
            <h4><strong>Operating Activities</strong></h4>
            @php $operatingTotal = 0; @endphp
            @foreach($operatingActivities as $item)
                @if($item['amount'] != 0)
                    <div class="d-flex justify-content-between">
                        <span>{{ $item['description'] }}</span>
                        <span class="{{ $item['amount'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($item['amount'], 2) }}
                        </span>
                    </div>
                    @php $operatingTotal += $item['amount']; @endphp
                @endif
            @endforeach
            <hr>
            <div class="d-flex justify-content-between">
                <strong>Net Cash from Operating</strong>
                <strong class="{{ $operatingTotal >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($operatingTotal, 2) }}
                </strong>
            </div>

            <h4 class="mt-4"><strong>Investing Activities</strong></h4>
            @php $investingTotal = 0; @endphp
            @foreach($investingActivities as $item)
                @if($item['amount'] != 0)
                    <div class="d-flex justify-content-between">
                        <span>{{ $item['description'] }}</span>
                        <span class="{{ $item['amount'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($item['amount'], 2) }}
                        </span>
                    </div>
                    @php $investingTotal += $item['amount']; @endphp
                @endif
            @endforeach
            <hr>
            <div class="d-flex justify-content-between">
                <strong>Net Cash from Investing</strong>
                <strong class="{{ $investingTotal >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($investingTotal, 2) }}
                </strong>
            </div>

            <h4 class="mt-4"><strong>Financing Activities</strong></h4>
            @php $financingTotal = 0; @endphp
            @foreach($financingActivities as $item)
                @if($item['amount'] != 0)
                    <div class="d-flex justify-content-between">
                        <span>{{ $item['description'] }}</span>
                        <span class="{{ $item['amount'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($item['amount'], 2) }}
                        </span>
                    </div>
                    @php $financingTotal += $item['amount']; @endphp
                @endif
            @endforeach
            <hr>
            <div class="d-flex justify-content-between">
                <strong>Net Cash from Financing</strong>
                <strong class="{{ $financingTotal >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($financingTotal, 2) }}
                </strong>
            </div>

            <hr>
            @php $netChange = $operatingTotal + $investingTotal + $financingTotal; @endphp
            <div class="d-flex justify-content-between">
                <h4><strong>Net Change in Cash</strong></h4>
                <h4 class="{{ $netChange >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($netChange, 2) }}
                </h4>
            </div>
        </div>
        <div class="card-footer">
            <a href="{{ route('finance.reports.index') }}" class="btn btn-secondary">Back to Reports</a>
        </div>
    </div>
@endsection
