@extends('layouts.erp')

@section('title', 'Tax Return')

@section('content_header')
    <h1>Tax Return</h1>
    <form method="GET" class="form-inline mt-2">
        <label for="from" class="mr-2">From</label>
        <input type="date" id="from" name="from" class="form-control mr-2" value="{{ $from }}">
        <label for="to" class="mr-2">to</label>
        <input type="date" id="to" name="to" class="form-control mr-2" value="{{ $to }}">
        <button type="submit" class="btn btn-primary mr-2">Show</button>
        <a href="{{ request()->fullUrlWithQuery(['format' => 'csv', 'from' => $from, 'to' => $to]) }}" class="btn btn-secondary">Export CSV</a>
    </form>
@endsection

@section('content')
    @php
        $sections = [
            'output' => 'Output tax on sales',
            'reverse_charge_output' => 'Reverse charge: tax self-assessed on purchases',
            'input' => 'Recoverable input tax',
            'non_recoverable' => 'Non-recoverable tax (included in cost, for information)',
            'withholding' => 'Tax withheld at source from supplier payments',
        ];
    @endphp

    @foreach ($sections as $box => $title)
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ $title }}</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Tax code</th><th>Name</th><th class="text-right">Taxable amount</th><th class="text-right">Tax</th></tr></thead>
                    <tbody>
                        @forelse ($summary['boxes'][$box] ?? [] as $row)
                            <tr>
                                <td>{{ $row['tax_code'] }}</td>
                                <td>{{ $row['tax_name'] }}</td>
                                <td class="text-right">{{ $row['taxable']->amount }}</td>
                                <td class="text-right">{{ $row['tax']->amount }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">None in this period.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr><th colspan="3">Total</th><th class="text-right">{{ $summary['totals'][$box]->amount }} {{ $summary['currency'] }}</th></tr></tfoot>
                </table>
            </div>
        </div>
    @endforeach

    <div class="card">
        <div class="card-body">
            <h4 class="mb-0">
                {{ $summary['net_payable']->isNegative() ? 'Net refundable' : 'Net payable' }}:
                {{ $summary['net_payable']->abs()->amount }} {{ $summary['currency'] }}
            </h4>
            <small class="text-muted">Output tax + reverse-charge tax − recoverable input tax, in the functional currency at each document's rate.</small>
        </div>
    </div>
@endsection
