@extends('layouts.erp')

@section('title', 'General Ledger')

@section('content_header')
    <h1>General Ledger</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('finance.general-ledger') }}" class="row g-3 mb-3">
                <div class="col-md-3">
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}" placeholder="Start Date">
                </div>
                <div class="col-md-3">
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}" placeholder="End Date">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>

            @if(empty($ledger['accounts']))
                <p class="text-muted">No ledger entries found.</p>
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
                                        <th>Date</th>
                                        <th>Journal #</th>
                                        <th>Description</th>
                                        <th>Cost Center</th>
                                        <th class="text-right">Debit</th>
                                        <th class="text-right">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($account['entries'] as $entry)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($entry['date'])->format('Y-m-d') }}</td>
                                            <td>{{ $entry['journal_number'] }}</td>
                                            <td>{{ $entry['description'] ?? '-' }}</td>
                                            <td>{{ $entry['cost_center'] ?? '-' }}</td>
                                            <td class="text-right">{{ number_format($entry['debit'], 2) }}</td>
                                            <td class="text-right">{{ number_format($entry['credit'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="table-active">
                                        <td colspan="4" class="text-end"><strong>Total</strong></td>
                                        <td class="text-right"><strong>{{ number_format($account['total_debit'], 2) }}</strong></td>
                                        <td class="text-right"><strong>{{ number_format($account['total_credit'], 2) }}</strong></td>
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
