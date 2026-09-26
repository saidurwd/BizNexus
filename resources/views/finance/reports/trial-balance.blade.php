@extends('layouts.erp')

@section('title', __('Trial Balance'))

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="m-0">{{ __('Trial Balance') }}</h1>
        <x-finance.export-button report="trial-balance" />
    </div>
@endsection

@section('content')
    <x-print-toolbar />
    <x-report-letterhead title="{{ __('Trial Balance') }}" />

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Trial Balance as of {{ $asOfDate }}</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>{{ __('Account Code') }}</th>
                        <th>{{ __('Account Name') }}</th>
                        <th class="text-end">{{ __('Opening Debit') }}</th>
                        <th class="text-end">{{ __('Opening Credit') }}</th>
                        <th class="text-end">{{ __('Period Debit') }}</th>
                        <th class="text-end">{{ __('Period Credit') }}</th>
                        <th class="text-end">{{ __('Closing Debit') }}</th>
                        <th class="text-end">{{ __('Closing Credit') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $totalOpeningDebit = 0; 
                        $totalOpeningCredit = 0; 
                        $totalPeriodDebit = 0; 
                        $totalPeriodCredit = 0; 
                        $totalClosingDebit = 0; 
                        $totalClosingCredit = 0; 
                    @endphp
                    @foreach($accounts as $account)
                        @if($account['period_debit'] != 0 || $account['period_credit'] != 0 || $account['opening_debit'] != 0 || $account['opening_credit'] != 0)
                            <tr>
                                <td>{{ $account['account_code'] }}</td>
                                <td>{{ $account['account_name'] }}</td>
                                <td class="text-end">{{ $account['opening_debit'] > 0 ? Formatter::amount($account['opening_debit']) : '-' }}</td>
                                <td class="text-end">{{ $account['opening_credit'] > 0 ? Formatter::amount($account['opening_credit']) : '-' }}</td>
                                <td class="text-end">{{ $account['period_debit'] > 0 ? Formatter::amount($account['period_debit']) : '-' }}</td>
                                <td class="text-end">{{ $account['period_credit'] > 0 ? Formatter::amount($account['period_credit']) : '-' }}</td>
                                <td class="text-end">{{ $account['closing_debit'] > 0 ? Formatter::amount($account['closing_debit']) : '-' }}</td>
                                <td class="text-end">{{ $account['closing_credit'] > 0 ? Formatter::amount($account['closing_credit']) : '-' }}</td>
                            </tr>
                            @php 
                                $totalOpeningDebit += $account['opening_debit'];
                                $totalOpeningCredit += $account['opening_credit'];
                                $totalPeriodDebit += $account['period_debit'];
                                $totalPeriodCredit += $account['period_credit'];
                                $totalClosingDebit += $account['closing_debit'];
                                $totalClosingCredit += $account['closing_credit'];
                            @endphp
                        @endif
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-secondary">
                        <td colspan="2" class="text-end"><strong>{{ __('TOTALS') }}</strong></td>
                        <td class="text-end"><strong>{{ Formatter::amount($totalOpeningDebit) }}</strong></td>
                        <td class="text-end"><strong>{{ Formatter::amount($totalOpeningCredit) }}</strong></td>
                        <td class="text-end"><strong>{{ Formatter::amount($totalPeriodDebit) }}</strong></td>
                        <td class="text-end"><strong>{{ Formatter::amount($totalPeriodCredit) }}</strong></td>
                        <td class="text-end"><strong>{{ Formatter::amount($totalClosingDebit) }}</strong></td>
                        <td class="text-end"><strong>{{ Formatter::amount($totalClosingCredit) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
