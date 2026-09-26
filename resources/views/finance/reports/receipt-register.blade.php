@extends('layouts.erp')

@section('title', __('Receipt Register'))

@section('content_header')
    <h1>{{ __('Receipt Register') }}</h1>
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
    <x-report-letterhead title="{{ __('Receipt Register') }}" />

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>Receipt #</th>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Method') }}</th>
                        <th>{{ __('Reference') }}</th>
                        <th class="text-end">{{ __('Amount') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $receipt)
                        <tr>
                            <td>{{ $receipt->receipt_date->format('Y-m-d') }}</td>
                            <td>{{ $receipt->receipt_number }}</td>
                            <td>{{ $receipt->customer?->name ?? '-' }}</td>
                            <td>{{ $receipt->receipt_method }}</td>
                            <td>{{ $receipt->reference ?? '-' }}</td>
                            <td class="text-end">{{ Formatter::amount($receipt->amount, $receipt->currency?->code) }} <small class="text-body-secondary">{{ $receipt->currency?->code }}</small></td>
                            <td>
                                <span class="badge bg-{{ $receipt->status === 'POSTED' ? 'success' : ($receipt->status === 'PENDING' ? 'warning' : 'secondary') }}">
                                    {{ $receipt->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">{{ __('No receipts found') }}</td>
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
