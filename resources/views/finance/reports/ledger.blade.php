@extends('layouts.erp')

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
                        <th class="text-end">Debit</th>
                        <th class="text-end">Credit</th>
                        <th class="text-end">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @php $balance = 0; @endphp
                    @foreach($entries as $entry)
                        <tr>
                            <td>{{ $entry['date']->format('Y-m-d') }}</td>
                            <td>{{ $entry['journal_number'] }}</td>
                            <td>{{ $entry['description'] }}</td>
                            <td class="text-end">{{ $entry['debit'] > 0 ? Formatter::amount($entry['debit']) : '-' }}</td>
                            <td class="text-end">{{ $entry['credit'] > 0 ? Formatter::amount($entry['credit']) : '-' }}</td>
                            <td class="text-end">
                                @php 
                                    $balance += $entry['debit'] - $entry['credit'];
                                    echo Formatter::amount($balance);
                                @endphp
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
@endsection
