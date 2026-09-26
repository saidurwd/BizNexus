@extends('layouts.erp')

@section('title', __('General Ledger'))

@section('content_header')
    <h1>{{ __('General Ledger') }}</h1>
@endsection

@section('content')
    <x-print-toolbar />
    <x-report-letterhead title="{{ __('General Ledger') }}" />

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('finance.reports.general-ledger') }}" class="row g-3 mb-3">
                <div class="col-md-3">
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}" placeholder="{{ __('Start Date') }}">
                </div>
                <div class="col-md-3">
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}" placeholder="{{ __('End Date') }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                </div>
            </form>

            @if(empty($ledger['accounts']))
                <p class="text-muted">{{ __('No ledger entries found.') }}</p>
            @else
                @foreach($ledger['accounts'] as $account)
                    <div class="card mb-3">
                        <div class="card-header">
                            <strong>{{ $account['account']['code'] }} - {{ $account['account']['name'] }}</strong>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-bordered table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('Date') }}</th>
                                        <th>Journal #</th>
                                        <th>{{ __('Description') }}</th>
                                        <th>{{ __('Cost Center') }}</th>
                                        <th class="text-end">{{ __('Debit') }}</th>
                                        <th class="text-end">{{ __('Credit') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($account['entries'] as $entry)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($entry['date'])->format('Y-m-d') }}</td>
                                            <td>{{ $entry['journal_number'] }}</td>
                                            <td>{{ $entry['description'] ?? '-' }}</td>
                                            <td>{{ $entry['cost_center'] ?? '-' }}</td>
                                            <td class="text-end">{{ Formatter::amount($entry['debit']) }}</td>
                                            <td class="text-end">{{ Formatter::amount($entry['credit']) }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="table-active">
                                        <td colspan="4" class="text-end"><strong>{{ __('Total') }}</strong></td>
                                        <td class="text-end"><strong>{{ Formatter::amount($account['total_debit']) }}</strong></td>
                                        <td class="text-end"><strong>{{ Formatter::amount($account['total_credit']) }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
@endsection
