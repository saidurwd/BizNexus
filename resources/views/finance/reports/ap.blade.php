@extends('layouts.erp')

@section('title', 'Accounts Payable Report')

@section('content_header')
    <h1>Accounts Payable Report</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">AP Aging Summary as of {{ $asOfDate }}</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th class="text-right">Current</th>
                        <th class="text-right">1-30 Days</th>
                        <th class="text-right">31-60 Days</th>
                        <th class="text-right">61-90 Days</th>
                        <th class="text-right">Over 90 Days</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalCurrent = 0; $total130 = 0; $total3160 = 0; $total6190 = 0; $totalOver90 = 0; @endphp
                    @forelse($apAging as $item)
                        <tr>
                            <td>{{ $item['supplier_name'] }}</td>
                            <td class="text-right">{{ number_format($item['current'], 2) }}</td>
                            <td class="text-right">{{ number_format($item['days_1_30'], 2) }}</td>
                            <td class="text-right">{{ number_format($item['days_31_60'], 2) }}</td>
                            <td class="text-right">{{ number_format($item['days_61_90'], 2) }}</td>
                            <td class="text-right">{{ number_format($item['over_90_days'], 2) }}</td>
                            <td class="text-right">{{ number_format($item['total'], 2) }}</td>
                        </tr>
                        @php
                            $totalCurrent += $item['current'];
                            $total130 += $item['days_1_30'];
                            $total3160 += $item['days_31_60'];
                            $total6190 += $item['days_61_90'];
                            $totalOver90 += $item['over_90_days'];
                        @endphp
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No payables found</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-secondary">
                        <td><strong>TOTAL</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalCurrent, 2) }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($total130, 2) }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($total3160, 2) }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($total6190, 2) }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalOver90, 2) }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalCurrent + $total130 + $total3160 + $total6190 + $totalOver90, 2) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="card-footer">
            <a href="{{ route('finance.dashboard') }}" class="btn btn-secondary">Back to Finance Dashboard</a>
        </div>
    </div>
@endsection
