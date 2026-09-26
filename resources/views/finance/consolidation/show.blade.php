@extends('layouts.erp')

@section('title', __('Consolidated Trial Balance'))

@section('content_header')
    <h1>Consolidated Trial Balance — {{ $group->name }}</h1>
    <form method="GET" class="form-inline mt-2">
        <label for="as_of" class="me-2">{{ __('As of') }}</label>
        <input type="date" id="as_of" name="as_of" class="form-control me-2" value="{{ $asOf }}">
        <button type="submit" class="btn btn-primary">{{ __('Show') }}</button>
    </form>
@endsection

@section('content')
    <x-report-letterhead title="{{ __('Consolidated Trial Balance') }}" />

    @php $currency = $report['currency']; @endphp

    <div class="card">
        <div class="card-body table-responsive">
            <p class="text-muted mb-2">Amounts in {{ $currency }}; debit positive, credit negative. Assets, liabilities and equity at the closing rate; income and expenses at the average rate.</p>
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>{{ __('Account') }}</th>
                        @foreach ($report['members'] as $member)
                            <th class="text-end">{{ $member['company']->code }}<br><small class="text-muted">{{ rtrim(rtrim($member['ownership_percent'], '0'), '.') }}% · rate {{ rtrim(rtrim($member['closing_rate'], '0'), '.') }}</small></th>
                        @endforeach
                        <th class="text-end">{{ __('Eliminations') }}</th>
                        <th class="text-end">{{ __('Consolidated') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['rows'] as $row)
                        <tr>
                            <td>{{ $row['account_code'] }} — {{ $row['account_name'] }}</td>
                            @foreach ($report['members'] as $member)
                                <td class="text-end">{{ ($row['by_company'][$member['company']->id] ?? null)?->amount ?? '—' }}</td>
                            @endforeach
                            <td class="text-end">{{ $row['eliminations']->isZero() ? '—' : $row['eliminations']->amount }}</td>
                            <td class="text-end"><strong>{{ $row['consolidated']->amount }}</strong></td>
                        </tr>
                    @endforeach
                    <tr class="table-active">
                        <td>{{ __('Foreign currency translation reserve (CTA)') }}</td>
                        <td colspan="{{ count($report['members']) + 1 }}"></td>
                        <td class="text-end"><strong>{{ $report['translation_difference']->amount }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-sm mb-0" style="max-width: 600px">
                <tr><th>{{ __('Consolidated profit') }}</th><td class="text-end">{{ $report['net_profit']->amount }} {{ $currency }}</td></tr>
                <tr><th>{{ __('of which attributable to non-controlling interests') }}</th><td class="text-end">{{ $report['nci_profit']->amount }} {{ $currency }}</td></tr>
                <tr><th>{{ __('Attributable to owners of the parent') }}</th><td class="text-end">{{ $report['net_profit']->minus($report['nci_profit'])->amount }} {{ $currency }}</td></tr>
                <tr><th>{{ __('Non-controlling interests in net assets') }}</th><td class="text-end">{{ $report['nci_net_assets']->amount }} {{ $currency }}</td></tr>
            </table>
        </div>
    </div>
@endsection
