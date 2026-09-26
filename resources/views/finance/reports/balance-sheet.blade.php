@extends('layouts.erp')

@section('title', __('Statement of Financial Position'))

@section('content_header')
    <h1>{{ __('Statement of Financial Position') }}</h1>
@endsection

@section('content')
    <form method="GET" action="{{ route('finance.reports.balance-sheet') }}" class="card mb-3">
        <div class="card-body row g-2 align-items-end">
            <div class="col-sm-4 col-md-3">
                <label for="as_of_date" class="form-label">{{ __('As of') }}</label>
                <input type="date" id="as_of_date" name="as_of_date" class="form-control" value="{{ $report['as_of_date'] }}">
            </div>
            <div class="col-sm-4 col-md-3">
                <button type="submit" class="btn btn-primary">{{ __('Show') }}</button>
                <x-finance.export-button report="balance-sheet" class="ms-2" />
            </div>
        </div>
    </form>

    @unless ($report['check']['is_balanced'])
        <div class="alert alert-danger">
            {{ __('Assets do not equal liabilities plus equity. Check for unbalanced or unclassified accounts.') }}
        </div>
    @endunless

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('As of :date', ['date' => $report['as_of_date']]) }}</h3>
        </div>
        <div class="card-body row">
            <div class="col-lg-6">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr><th colspan="2">{{ __('Assets') }}</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($report['assets']['accounts'] as $account)
                            <tr>
                                <td>{{ $account['account_code'] }} {{ $account['account_name'] }}</td>
                                <td class="text-end">{{ Formatter::amount($account['amount']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td>{{ __('Total assets') }}</td>
                            <td class="text-end">{{ Formatter::amount($report['assets']['total']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="col-lg-6">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr><th colspan="2">{{ __('Liabilities') }}</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($report['liabilities']['accounts'] as $account)
                            <tr>
                                <td>{{ $account['account_code'] }} {{ $account['account_name'] }}</td>
                                <td class="text-end">{{ Formatter::amount($account['amount']) }}</td>
                            </tr>
                        @endforeach
                        <tr class="fw-bold">
                            <td>{{ __('Total liabilities') }}</td>
                            <td class="text-end">{{ Formatter::amount($report['liabilities']['total']) }}</td>
                        </tr>
                    </tbody>
                    <thead class="table-light">
                        <tr><th colspan="2">{{ __('Equity') }}</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($report['equity']['accounts'] as $account)
                            <tr>
                                <td>{{ $account['account_code'] }} {{ $account['account_name'] }}</td>
                                <td class="text-end">{{ Formatter::amount($account['amount']) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td>{{ __('Profit for the current year') }}</td>
                            <td class="text-end">{{ Formatter::amount($report['equity']['current_year_profit']) }}</td>
                        </tr>
                        <tr class="fw-bold">
                            <td>{{ __('Total equity') }}</td>
                            <td class="text-end">{{ Formatter::amount($report['equity']['total']) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td>{{ __('Total liabilities and equity') }}</td>
                            <td class="text-end">{{ Formatter::amount($report['total_liabilities_equity']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
