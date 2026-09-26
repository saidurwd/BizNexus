@extends('layouts.erp')

@section('title', __('Supplier credit notes'))

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="m-0">{{ __('Supplier credit notes') }}</h1>
        @can('finance.supplier-credit-notes.create')
            <a href="{{ route('finance.supplier-credit-notes.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> {{ __('New credit note') }}</a>
        @endcan
    </div>
@endsection

@section('content')
    <form method="GET" class="card mb-3">
        <div class="card-body row g-2 align-items-end">
            <div class="col-sm-4 col-md-3">
                <label for="status" class="form-label">{{ __('Status') }}</label>
                <select id="status" name="status" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    @foreach (['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED', 'POSTED', 'CANCELLED'] as $option)
                        <option value="{{ $option }}" @selected($status === $option)>{{ __(ucfirst(strtolower($option))) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">{{ __('Filter') }}</button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ __('Number') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Supplier') }}</th>
                        <th>{{ __('Invoice credited') }}</th>
                        <th class="text-end">{{ __('Total') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($creditNotes as $creditNote)
                        <tr>
                            <td><a href="{{ route('finance.supplier-credit-notes.show', $creditNote->id) }}">{{ $creditNote->credit_note_number }}</a></td>
                            <td>{{ Formatter::date($creditNote->credit_note_date) }}</td>
                            <td>{{ $creditNote->supplier?->name }}</td>
                            <td>{{ $creditNote->invoice?->invoice_number ?? '—' }}</td>
                            <td class="text-end">{{ Formatter::amount($creditNote->total_amount, $creditNote->currency?->code) }} <small class="text-body-secondary">{{ $creditNote->currency?->code }}</small></td>
                            <td><x-status-badge :status="$creditNote->status" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-body-secondary py-4">{{ __('No credit notes yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($creditNotes->hasPages())
            <div class="card-footer">{{ $creditNotes->links() }}</div>
        @endif
    </div>
@endsection
