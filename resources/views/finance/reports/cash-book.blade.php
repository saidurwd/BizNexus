@extends('adminlte::page')

@section('title', 'Cash Book')

@section('content_header')
    <h1>Cash Book</h1>
    <div class="mt-2">
        <form method="GET" class="d-inline">
            <input type="date" name="start_date" class="form-control d-inline-block" style="width:auto;" value="{{ $startDate }}">
            <input type="date" name="end_date" class="form-control d-inline-block" style="width:auto;" value="{{ $endDate }}">
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>
        <button class="btn btn-secondary" onclick="window.print()">
            <i class="bi bi-printer"></i> Print
        </button>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <strong>Opening Balance:</strong> {{ number_format($openingBalance, 2) }}
                </div>
                <div class="col-md-3">
                    <strong>Closing Balance:</strong> {{ number_format($closingBalance, 2) }}
                </div>
                <div class="col-md-3">
                    <strong>Period:</strong> {{ $startDate }} to {{ $endDate }}
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Transaction #</th>
                        <th>Account</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th class="text-right">Receipt</th>
                        <th class="text-right">Payment</th>
                        <th class="text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @php $runningBalance = $openingBalance; @endphp
                    <tr class="table-active">
                        <td colspan="5"><strong>Opening Balance</strong></td>
                        <td class="text-right">-</td>
                        <td class="text-right">-</td>
                        <td class="text-right"><strong>{{ number_format($openingBalance, 2) }}</strong></td>
                    </tr>
                    @forelse($transactions as $transaction)
                        @php
                            $receipt = $transaction->transaction_type === 'DEPOSIT' ? $transaction->amount : 0;
                            $payment = in_array($transaction->transaction_type, ['WITHDRAWAL', 'TRANSFER', 'CHARGE']) ? $transaction->amount : 0;
                            $runningBalance += $receipt - $payment;
                        @endphp
                        <tr>
                            <td>{{ $transaction->transaction_date->format('Y-m-d') }}</td>
                            <td>{{ $transaction->transaction_number }}</td>
                            <td>{{ $transaction->bankAccount?->bank_name ?? '-' }}</td>
                            <td>{{ $transaction->transaction_type }}</td>
                            <td>{{ $transaction->description ?? '-' }}</td>
                            <td class="text-right">{{ $receipt > 0 ? number_format($receipt, 2) : '-' }}</td>
                            <td class="text-right">{{ $payment > 0 ? number_format($payment, 2) : '-' }}</td>
                            <td class="text-right">{{ number_format($runningBalance, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No transactions found</td>
                        </tr>
                    @endforelse
                    <tr class="table-active">
                        <td colspan="5"><strong>Closing Balance</strong></td>
                        <td class="text-right">-</td>
                        <td class="text-right">-</td>
                        <td class="text-right"><strong>{{ number_format($closingBalance, 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
