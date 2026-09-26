@extends('layouts.erp')

@section('title', __('Customers'))

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="m-0">{{ __('Customers') }}</h1>
        @can('finance.customers.create')
            <a href="{{ route('finance.customers.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> {{ __('New customer') }}</a>
        @endcan
    </div>
@endsection

@section('content')
    <form method="GET" class="card mb-3">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-5">
                <label for="q" class="form-label">{{ __('Search') }}</label>
                <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="{{ __('Name, code, email or tax number') }}">
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">{{ __('Status') }}</label>
                <select id="status" name="status" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>{{ __('Active') }}</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>{{ __('Inactive') }}</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Contact') }}</th>
                        <th>{{ __('Tax number') }}</th>
                        <th>{{ __('Payment term') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $party)
                        <tr>
                            <td><code>{{ $party->customer_code }}</code></td>
                            <td><a href="{{ route('finance.customers.show', $party->id) }}">{{ $party->name }}</a></td>
                            <td>{{ $party->contact_person }} <small class="text-body-secondary">{{ $party->email }}</small></td>
                            <td>{{ $party->tax_number ?? '—' }}</td>
                            <td>{{ $party->paymentTerm?->code ?? '—' }}</td>
                            <td><x-status-badge :status="$party->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">{{ __('No customers found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($customers->hasPages())
            <div class="card-footer">{{ $customers->links() }}</div>
        @endif
    </div>
@endsection
