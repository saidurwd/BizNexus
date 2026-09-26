@extends('layouts.erp')

@section('title', __('Statement of Cash Flows'))

@section('content_header')
    <h1>{{ __('Statement of Cash Flows') }}</h1>
@endsection

@section('content')
    <x-print-toolbar />
    <x-report-letterhead :title="__('Statement of Cash Flows')" />

    <form method="GET" action="{{ route('finance.reports.cash-flow') }}" class="card mb-3">
        <div class="card-body row g-2 align-items-end">
            <div class="col-sm-4 col-md-3">
                <label for="start_date" class="form-label">{{ __('From') }}</label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="{{ $report['start_date'] }}">
            </div>
            <div class="col-sm-4 col-md-3">
                <label for="end_date" class="form-label">{{ __('To') }}</label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="{{ $report['end_date'] }}">
            </div>
            <div class="col-sm-4 col-md-3">
                <button type="submit" class="btn btn-primary">{{ __('Show') }}</button>
                <x-finance.export-button report="cash-flow" class="ms-2" />
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('For the period :from to :to (direct method)', ['from' => $report['start_date'], 'to' => $report['end_date']]) }}</h3>
        </div>
        <div class="card-body">
            @unless ($report['is_reconciled'])
                <div class="alert alert-warning">
                    {{ __('Opening cash plus the movements shown does not equal closing cash. Check that every cash and bank account is classified as cash and cash equivalents.') }}
                </div>
            @endunless

            <table class="table table-sm">
                @foreach (['operating' => __('Cash flows from operating activities'), 'investing' => __('Cash flows from investing activities'), 'financing' => __('Cash flows from financing activities')] as $category => $heading)
                    <tbody>
                        <tr class="table-light">
                            <th colspan="2">{{ $heading }}</th>
                        </tr>
                        @forelse ($report[$category.'_activities'] as $item)
                            <tr>
                                <td class="ps-4">{{ $item['description'] }}</td>
                                <td class="text-end">{{ Formatter::amount($item['amount']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="ps-4 text-body-secondary" colspan="2">{{ __('No movements') }}</td>
                            </tr>
                        @endforelse
                        <tr>
                            <th>{{ __('Net cash from :activity', ['activity' => __($category.' activities')]) }}</th>
                            <th class="text-end">{{ Formatter::amount($report[$category.'_total']) }}</th>
                        </tr>
                    </tbody>
                @endforeach
                <tbody>
                    <tr>
                        <td>{{ __('Effect of exchange rate changes on cash') }}</td>
                        <td class="text-end">{{ Formatter::amount($report['fx_effect']) }}</td>
                    </tr>
                    <tr class="border-top">
                        <th>{{ __('Net change in cash and cash equivalents') }}</th>
                        <th class="text-end">{{ Formatter::amount($report['net_change']) }}</th>
                    </tr>
                    <tr>
                        <td>{{ __('Cash and cash equivalents at the start of the period') }}</td>
                        <td class="text-end">{{ Formatter::amount($report['opening_cash']) }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('Cash and cash equivalents at the end of the period') }}</th>
                        <th class="text-end">{{ Formatter::amount($report['closing_cash']) }}</th>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
