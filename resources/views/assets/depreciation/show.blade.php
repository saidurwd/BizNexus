@extends('layouts.erp')

@section('title', __('Depreciation for :month', ['month' => $run->period_end->translatedFormat('F Y')]))

@section('content_header')
    <div class="d-flex flex-wrap align-items-center gap-2">
        <h1 class="m-0">{{ __('Depreciation for :month', ['month' => $run->period_end->translatedFormat('F Y')]) }}</h1>
        <x-status-badge :status="$run->status" class="fs-6" />
    </div>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">{{ __('Total') }}</dt>
                <dd class="col-sm-9">{{ Formatter::amount($run->total_amount) }} · {{ trans_choice(':count asset|:count assets', $run->asset_count, ['count' => $run->asset_count]) }}</dd>
                <dt class="col-sm-3">{{ __('Journal') }}</dt>
                <dd class="col-sm-9">@if ($run->journal)<a href="{{ route('finance.journals.show', $run->journal_id) }}">{{ $run->journal->journal_number }}</a>@endif
                    @if ($run->reversalJournal) · {{ __('Reversed by') }} <a href="{{ route('finance.journals.show', $run->reversal_journal_id) }}">{{ $run->reversalJournal->journal_number }}</a>@endif
                </dd>
                <dt class="col-sm-3">{{ __('Posted by') }}</dt>
                <dd class="col-sm-9">{{ $run->createdBy?->name ?? '—' }} · {{ Formatter::date($run->created_at) }}</dd>
            </dl>
        </div>
    </div>

    @if ($run->transactions->isNotEmpty())
        <div class="card mb-3">
            <div class="card-body table-responsive p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>{{ __('Asset') }}</th><th>{{ __('Category') }}</th><th class="text-end">{{ __('Months') }}</th><th class="text-end">{{ __('Depreciation') }}</th></tr></thead>
                    <tbody>
                        @foreach ($run->transactions as $transaction)
                            <tr>
                                <td><a href="{{ route('assets.assets.show', $transaction->fixed_asset_id) }}">{{ $transaction->asset?->asset_number }}</a> {{ $transaction->asset?->name }}</td>
                                <td>{{ $transaction->asset?->category?->name }}</td>
                                <td class="text-end">{{ $transaction->details['months'] ?? 1 }}</td>
                                <td class="text-end">{{ Formatter::amount($transaction->amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('assets.depreciation.index') }}" class="btn btn-secondary">{{ __('Back') }}</a>
        @if ($run->status === 'POSTED')
            @can('assets.depreciate')
                <form method="POST" action="{{ route('assets.depreciation.reverse', $run->id) }}" class="d-flex gap-2" onsubmit="return confirm(@js(__('Reverse this depreciation run?')))">
                    @csrf
                    <input type="text" name="reason" class="form-control" placeholder="{{ __('Reason') }}" maxlength="500">
                    <button type="submit" class="btn btn-outline-danger text-nowrap">{{ __('Reverse run') }}</button>
                </form>
            @endcan
        @endif
    </div>
@endsection
