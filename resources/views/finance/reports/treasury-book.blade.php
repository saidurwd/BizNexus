@extends('layouts.erp')

@section('title', $title)

@section('content_header')
    <h1>{{ $title }}</h1>
    <div class="mt-2">
        <form method="GET" class="d-inline">
            <input type="date" name="start_date" class="form-control d-inline-block" style="width:auto;" value="{{ $startDate }}">
            <input type="date" name="end_date" class="form-control d-inline-block" style="width:auto;" value="{{ $endDate }}">
            <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
        </form>
        <button class="btn btn-secondary" onclick="window.print()">
            <i class="bi bi-printer"></i> {{ __('Print') }}
        </button>
    </div>
@endsection

@section('content')
    <x-report-letterhead :title="$title" />

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>Journal #</th>
                        <th>{{ __('Account') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th class="text-end">{{ __('Receipt') }}</th>
                        <th class="text-end">{{ __('Payment') }}</th>
                        <th class="text-end">{{ __('Balance') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php $runningBalance = $openingBalance; @endphp
                    <tr class="table-active">
                        <td colspan="6"><strong>Opening balance {{ $startDate }}</strong></td>
                        <td class="text-end"><strong>{{ Formatter::amount($openingBalance) }}</strong></td>
                    </tr>
                    @forelse ($lines as $line)
                        @php $runningBalance += (float) $line->debit - (float) $line->credit; @endphp
                        <tr>
                            <td>{{ $line->journal->journal_date->format('Y-m-d') }}</td>
                            <td><a href="{{ route('finance.journals.show', $line->journal_id) }}">{{ $line->journal->journal_number }}</a></td>
                            <td>{{ $line->account->account_code }} — {{ $line->account->account_name }}</td>
                            <td>{{ $line->description ?? $line->journal->description }}</td>
                            <td class="text-end">{{ (float) $line->debit > 0 ? Formatter::amount($line->debit) : '-' }}</td>
                            <td class="text-end">{{ (float) $line->credit > 0 ? Formatter::amount($line->credit) : '-' }}</td>
                            <td class="text-end">{{ Formatter::amount($runningBalance) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">{{ __('No transactions in this period.') }}</td></tr>
                    @endforelse
                    <tr class="table-active">
                        <td colspan="6"><strong>Closing balance {{ $endDate }}</strong></td>
                        <td class="text-end"><strong>{{ Formatter::amount($closingBalance) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
