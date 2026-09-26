@extends('layouts.erp')

@section('title', __('Payment Register'))

@section('content_header')
    <h1>{{ __('Payment Register') }}</h1>
    <div class="mt-2">
        <form method="GET" class="d-inline">
            <input type="date" name="start_date" class="form-control d-inline-block" style="width:auto;" value="{{ $startDate }}">
            <input type="date" name="end_date" class="form-control d-inline-block" style="width:auto;" value="{{ $endDate }}">
            <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
        </form>
        <button class="btn btn-secondary" onclick="window.print()">
            <i class="bi bi-printer"></i> {{ __('Print') }}
        </button>
    </div>
@endsection

@section('content')
    <x-report-letterhead title="{{ __('Payment Register') }}" />

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>Payment #</th>
                        <th>{{ __('Supplier') }}</th>
                        <th>{{ __('Method') }}</th>
                        <th>{{ __('Reference') }}</th>
                        <th class="text-end">{{ __('Amount') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                            <td>{{ $payment->payment_number }}</td>
                            <td>{{ $payment->supplier?->name ?? '-' }}</td>
                            <td>{{ $payment->payment_method }}</td>
                            <td>{{ $payment->reference ?? '-' }}</td>
                            <td class="text-end">{{ Formatter::amount($payment->amount, $payment->currency?->code) }} <small class="text-body-secondary">{{ $payment->currency?->code }}</small></td>
                            <td>
                                <span class="badge bg-{{ $payment->status === 'POSTED' ? 'success' : ($payment->status === 'PENDING' ? 'warning' : 'secondary') }}">
                                    {{ $payment->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">{{ __('No payments found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="table-active">
                        <td colspan="5" class="text-end"><strong>{{ __('Total posted, functional currency') }}</strong></td>
                        <td class="text-end"><strong>{{ Formatter::amount($totalAmount) }}</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
