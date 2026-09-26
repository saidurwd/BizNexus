@extends('layouts.erp')

@section('title', $title)

@section('content_header')
    <h1>{{ $title }}</h1>
@endsection

@section('content')
    <x-report-letterhead :title="$title" />

    <form method="GET" action="{{ route($routeName) }}" class="card mb-3">
        <div class="card-body row g-2 align-items-end">
            <div class="col-sm-4 col-md-3">
                <label for="as_of_date" class="form-label">{{ __('As of') }}</label>
                <input type="date" id="as_of_date" name="as_of_date" class="form-control" value="{{ $aging['as_of_date'] }}">
            </div>
            <div class="col-sm-4 col-md-3">
                <button type="submit" class="btn btn-primary">{{ __('Show') }}</button>
                <x-finance.export-button :report="$exportReport" class="ms-2" />
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Days past due as of :date, in the functional currency', ['date' => $aging['as_of_date']]) }}</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ $partyLabel }}</th>
                        <th class="text-end">{{ __('Not yet due') }}</th>
                        <th class="text-end">{{ __('1–30 days') }}</th>
                        <th class="text-end">{{ __('31–60 days') }}</th>
                        <th class="text-end">{{ __('61–90 days') }}</th>
                        <th class="text-end">{{ __('Over 90 days') }}</th>
                        <th class="text-end">{{ __('Total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($aging['parties'] as $party)
                        <tr>
                            <td>{{ $party['party_name'] }}</td>
                            <td class="text-end">{{ Formatter::amount($party['current']) }}</td>
                            <td class="text-end">{{ Formatter::amount($party['days_1_30']) }}</td>
                            <td class="text-end">{{ Formatter::amount($party['days_31_60']) }}</td>
                            <td class="text-end">{{ Formatter::amount($party['days_61_90']) }}</td>
                            <td class="text-end">{{ Formatter::amount($party['over_90_days']) }}</td>
                            <td class="text-end fw-bold">{{ Formatter::amount($party['total']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-body-secondary">{{ __('No open invoices.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td>{{ __('Total') }}</td>
                        <td class="text-end">{{ Formatter::amount($aging['current']) }}</td>
                        <td class="text-end">{{ Formatter::amount($aging['days_1_30']) }}</td>
                        <td class="text-end">{{ Formatter::amount($aging['days_31_60']) }}</td>
                        <td class="text-end">{{ Formatter::amount($aging['days_61_90']) }}</td>
                        <td class="text-end">{{ Formatter::amount(bcadd($aging['days_91_180'], $aging['days_180_plus'], 4)) }}</td>
                        <td class="text-end">{{ Formatter::amount($aging['total']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
