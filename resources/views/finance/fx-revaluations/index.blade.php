@extends('layouts.erp')

@section('title', __('Foreign Currency Revaluation'))

@section('content_header')
    <h1>{{ __('Foreign Currency Revaluation') }}</h1>
    <p class="text-muted mb-0">{{ __('Restates foreign-currency balances of accounts marked for revaluation at the closing rate. The entry reverses automatically on the next day.') }}</p>
@endsection

@section('content')
    @can('finance.fx-revaluation.run')
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('finance.fx-revaluations.store') }}" class="form-inline">
                    @csrf
                    <label for="revaluation_date" class="me-2">{{ __('Revaluation date') }}</label>
                    <input type="date" id="revaluation_date" name="revaluation_date" class="form-control me-2" value="{{ old('revaluation_date', now()->endOfMonth()->toDateString()) }}" required>
                    <button type="submit" class="btn btn-primary">{{ __('Revalue') }}</button>
                    @error('revaluation_date')<div class="text-danger ms-2">{{ $message }}</div>@enderror
                </form>
            </div>
        </div>
    @endcan

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th class="text-end">{{ __('Net unrealised gain / (loss)') }}</th>
                        <th>{{ __('Journal') }}</th>
                        <th>{{ __('Reversal') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($revaluations as $revaluation)
                        <tr>
                            <td>{{ $revaluation->revaluation_date->format('Y-m-d') }}</td>
                            <td class="text-end">{{ Formatter::amount($revaluation->net_gain_loss) }}</td>
                            <td>
                                @if ($revaluation->journal)
                                    <a href="{{ route('finance.journals.show', $revaluation->journal_id) }}">{{ $revaluation->journal->journal_number }}</a>
                                @else
                                    <span class="text-muted">{{ __('No differences') }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($revaluation->reversalJournal)
                                    <a href="{{ route('finance.journals.show', $revaluation->reversal_journal_id) }}">{{ $revaluation->reversalJournal->journal_number }}</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">{{ __('No revaluations yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $revaluations->links() }}
        </div>
    </div>
@endsection
