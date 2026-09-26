@extends('layouts.erp')

@section('title', __('Depreciation'))

@section('content_header')
    <h1>{{ __('Depreciation') }}</h1>
    <p class="text-body-secondary mb-0">{{ __('Each run charges every asset in use up to the chosen month end, catching up any months missed, and posts one journal.') }}</p>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">{{ __('Run depreciation') }}</h3></div>
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end mb-3">
                <div class="col-md-3">
                    <label for="period" class="form-label">{{ __('Up to the end of') }}</label>
                    <input type="month" id="period" name="period" value="{{ $period->format('Y-m') }}" class="form-control">
                </div>
                <div class="col-auto"><button type="submit" class="btn btn-outline-primary">{{ __('Preview') }}</button></div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>{{ __('Asset') }}</th><th>{{ __('Category') }}</th><th class="text-end">{{ __('Months') }}</th><th class="text-end">{{ __('Depreciation') }}</th></tr></thead>
                    <tbody>
                        @forelse ($preview as $row)
                            <tr>
                                <td><a href="{{ route('assets.assets.show', $row['asset']->id) }}">{{ $row['asset']->asset_number }}</a> {{ $row['asset']->name }}</td>
                                <td>{{ $row['asset']->category?->name }}</td>
                                <td class="text-end">{{ $row['months'] }}</td>
                                <td class="text-end">{{ Formatter::amount($row['amount']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-body-secondary py-3">{{ __('Nothing to charge up to this month.') }}</td></tr>
                        @endforelse
                    </tbody>
                    @if ($preview->isNotEmpty())
                        <tfoot><tr class="fw-bold"><td colspan="3">{{ __('Total') }}</td><td class="text-end">{{ Formatter::amount($preview->reduce(fn ($sum, $row) => bcadd($sum, $row['amount'], 4), '0')) }}</td></tr></tfoot>
                    @endif
                </table>
            </div>
        </div>
        @if ($preview->isNotEmpty())
            @can('assets.depreciate')
                <div class="card-footer">
                    <form method="POST" action="{{ route('assets.depreciation.store') }}" onsubmit="return confirm(@js(__('Post depreciation up to :month?', ['month' => $period->translatedFormat('F Y')])))">
                        @csrf
                        <input type="hidden" name="period" value="{{ $period->format('Y-m') }}">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-journal-check"></i> {{ __('Post depreciation for :month', ['month' => $period->translatedFormat('F Y')]) }}</button>
                    </form>
                </div>
            @endcan
        @endif
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Posted runs') }}</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>{{ __('Month') }}</th><th class="text-end">{{ __('Assets') }}</th><th class="text-end">{{ __('Total') }}</th><th>{{ __('Journal') }}</th><th>{{ __('Posted by') }}</th><th>{{ __('Status') }}</th></tr></thead>
                <tbody>
                    @forelse ($runs as $run)
                        <tr>
                            <td><a href="{{ route('assets.depreciation.show', $run->id) }}">{{ $run->period_end->translatedFormat('F Y') }}</a></td>
                            <td class="text-end">{{ $run->asset_count }}</td>
                            <td class="text-end">{{ Formatter::amount($run->total_amount) }}</td>
                            <td>@if ($run->journal)<a href="{{ route('finance.journals.show', $run->journal_id) }}">{{ $run->journal->journal_number }}</a>@endif</td>
                            <td>{{ $run->createdBy?->name }}</td>
                            <td><x-status-badge :status="$run->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">{{ __('No depreciation has been posted yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($runs->hasPages())
            <div class="card-footer">{{ $runs->links() }}</div>
        @endif
    </div>
@endsection
