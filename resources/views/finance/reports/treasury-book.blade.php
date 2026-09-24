@extends('layouts.erp')

@section('title', $title)

@section('content_header')
    <h1>{{ $title }}</h1>
    <div class="mt-2">
        <form method="GET" class="d-inline">
            <input type="date" name="start_date" class="form-control d-inline-block" style="width:auto;" value="{{ $startDate }}">
            <input type="date" name="end_date" class="form-control d-inline-block" style="width:auto;" value="{{ $endDate }}">
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>
        <button class="btn btn-secondary" onclick="window.print()">
            <i class="bi bi-printer"></i> Print
        </button>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Journal #</th>
                        <th>Account</th>
                        <th>Description</th>
                        <th class="text-right">Receipt</th>
                        <th class="text-right">Payment</th>
                        <th class="text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @php $runningBalance = $openingBalance; @endphp
                    <tr class="table-active">
                        <td colspan="6"><strong>Opening balance {{ $startDate }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($openingBalance, 2) }}</strong></td>
                    </tr>
                    @forelse ($lines as $line)
                        @php $runningBalance += (float) $line->debit - (float) $line->credit; @endphp
                        <tr>
                            <td>{{ $line->journal->journal_date->format('Y-m-d') }}</td>
                            <td><a href="{{ route('finance.journals.show', $line->journal_id) }}">{{ $line->journal->journal_number }}</a></td>
                            <td>{{ $line->account->account_code }} — {{ $line->account->account_name }}</td>
                            <td>{{ $line->description ?? $line->journal->description }}</td>
                            <td class="text-right">{{ (float) $line->debit > 0 ? number_format((float) $line->debit, 2) : '-' }}</td>
                            <td class="text-right">{{ (float) $line->credit > 0 ? number_format((float) $line->credit, 2) : '-' }}</td>
                            <td class="text-right">{{ number_format($runningBalance, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No transactions in this period.</td></tr>
                    @endforelse
                    <tr class="table-active">
                        <td colspan="6"><strong>Closing balance {{ $endDate }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($closingBalance, 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
