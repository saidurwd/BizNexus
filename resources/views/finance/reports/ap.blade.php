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
                        <th class="text-end">Current</th>
                        <th class="text-end">1-30 Days</th>
                        <th class="text-end">31-60 Days</th>
                        <th class="text-end">61-90 Days</th>
                        <th class="text-end">Over 90 Days</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalCurrent = 0; $total130 = 0; $total3160 = 0; $total6190 = 0; $totalOver90 = 0; @endphp
                    @forelse($apAging as $item)
                        <tr>
                            <td>{{ $item['supplier_name'] }}</td>
                            <td class="text-end">{{ Formatter::amount($item['current']) }}</td>
                            <td class="text-end">{{ Formatter::amount($item['days_1_30']) }}</td>
                            <td class="text-end">{{ Formatter::amount($item['days_31_60']) }}</td>
                            <td class="text-end">{{ Formatter::amount($item['days_61_90']) }}</td>
                            <td class="text-end">{{ Formatter::amount($item['over_90_days']) }}</td>
                            <td class="text-end">{{ Formatter::amount($item['total']) }}</td>
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
                        <td class="text-end"><strong>{{ Formatter::amount($totalCurrent) }}</strong></td>
                        <td class="text-end"><strong>{{ Formatter::amount($total130) }}</strong></td>
                        <td class="text-end"><strong>{{ Formatter::amount($total3160) }}</strong></td>
                        <td class="text-end"><strong>{{ Formatter::amount($total6190) }}</strong></td>
                        <td class="text-end"><strong>{{ Formatter::amount($totalOver90) }}</strong></td>
                        <td class="text-end"><strong>{{ Formatter::amount($totalCurrent + $total130 + $total3160 + $total6190 + $totalOver90) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="card-footer">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
@endsection
