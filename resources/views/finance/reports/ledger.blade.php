@extends('adminlte::page')

@section('title', 'General Ledger')

@section('content_header')
    <h1>General Ledger</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Account: {{ $account->account_name }} ({{ $account->account_code }})</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Journal #</th>
                        <th>Description</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                        <th class="text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @php $balance = 0; @endphp
                    @foreach($entries as $entry)
                        <tr>
                            <td>{{ $entry['date']->format('Y-m-d') }}</td>
                            <td>{{ $entry['journal_number'] }}</td>
                            <td>{{ $entry['description'] }}</td>
                            <td class="text-right">{{ $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '-' }}</td>
                            <td class="text-right">{{ $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '-' }}</td>
                            <td class="text-right">
                                @php 
                                    $balance += $entry['debit'] - $entry['credit'];
                                    echo number_format($balance, 2);
                                @endphp
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <a href="{{ route('finance.reports.index') }}" class="btn btn-secondary">Back to Reports</a>
        </div>
    </div>
@endsection
