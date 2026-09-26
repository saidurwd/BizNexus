@extends('layouts.erp')

@section('title', 'Supplier Statement - ' . $supplier->name)

@section('content_header')
    <h1>{{ __('Supplier statement: :name', ['name' => $supplier->name]) }}</h1>
    <div class="mt-2">
        <a href="{{ route('finance.supplier-statements.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('Back') }}
        </a>
        <button class="btn btn-primary" onclick="window.print()">
            <i class="bi bi-printer"></i> {{ __('Print') }}
        </button>
    </div>
@endsection

@section('content')
    <x-report-letterhead :title="'Supplier Statement - ' . $supplier->name" />

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('finance.supplier-statements.show', $supplier->id) }}" class="row g-3 mb-3">
                <div class="col-md-3">
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" placeholder="{{ __('Start Date') }}">
                </div>
                <div class="col-md-3">
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" placeholder="{{ __('End Date') }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                </div>
            </form>

            <div class="row mb-3">
                <div class="col-md-3">
                    <strong>{{ __('Supplier:') }}</strong> {{ $supplier->name }}
                </div>
                <div class="col-md-3">
                    <strong>{{ __('Code:') }}</strong> {{ $supplier->supplier_code }}
                </div>
                <div class="col-md-3">
                    <strong>{{ __('Period:') }}</strong> {{ $startDate ?? 'Beginning' }} to {{ $endDate ?? 'Current' }}
                    <div class="small text-body-secondary">{{ __('Posted documents only, in the functional currency.') }}</div>
                </div>
                <div class="col-md-3 text-end">
                    <strong>{{ __('Closing Balance:') }}</strong> {{ Formatter::amount($statement['closing_balance']) }}
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Number') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th class="text-end">{{ __('Debit') }}</th>
                            <th class="text-end">{{ __('Credit') }}</th>
                            <th class="text-end">{{ __('Balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-active">
                            <td colspan="4"><strong>{{ __('Opening Balance') }}</strong></td>
                            <td class="text-end"><strong>{{ Formatter::amount($statement['opening_balance']) }}</strong></td>
                            <td class="text-end">-</td>
                            <td class="text-end"><strong>{{ Formatter::amount($statement['opening_balance']) }}</strong></td>
                        </tr>
                        @foreach($statement['entries'] as $entry)
                            <tr>
                                <td>{{ Formatter::date($entry['date']) }}</td>
                                <td>
                                    <span class="badge text-bg-{{ $entry['type'] === 'invoice' ? 'primary' : (str_ends_with($entry['type'], 'note') ? 'info' : 'success') }}">
                                        {{ __(ucfirst(str_replace('_', ' ', $entry['type']))) }}
                                    </span>
                                </td>
                                <td>{{ $entry['number'] }}</td>
                                <td>{{ $entry['description'] }}</td>
                                <td class="text-end">{{ $entry['debit'] > 0 ? Formatter::amount($entry['debit']) : '-' }}</td>
                                <td class="text-end">{{ $entry['credit'] > 0 ? Formatter::amount($entry['credit']) : '-' }}</td>
                                <td class="text-end">{{ Formatter::amount($entry['balance']) }}</td>
                            </tr>
                        @endforeach
                        <tr class="table-active">
                            <td colspan="4"><strong>{{ __('Closing Balance') }}</strong></td>
                            <td class="text-end"><strong>{{ Formatter::amount($statement['total_invoices']) }}</strong></td>
                            <td class="text-end"><strong>{{ Formatter::amount(bcadd($statement['total_payments'], $statement['total_credits'], 4)) }}</strong></td>
                            <td class="text-end"><strong>{{ Formatter::amount($statement['closing_balance']) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
