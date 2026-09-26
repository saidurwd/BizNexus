@extends('layouts.erp')

@section('title', __('Activity Logs'))

@section('content_header')
    <h1>{{ __('Activity Logs') }}</h1>
    <p class="text-body-secondary mb-0">{{ __('Every change made through the application and every data download. Before-and-after values are in the audit log.') }}</p>
@endsection

@section('content')
    <form method="GET" class="card mb-3">
        <div class="card-body row g-2 align-items-end">
            @include('core.security._user-filter')
            <div class="col-sm-6 col-md-3">
                <label for="q" class="form-label">{{ __('Page or action') }}</label>
                <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="{{ __('e.g. invoices, journals.post') }}">
            </div>
            <div class="col-sm-3 col-md-1">
                <label for="method" class="form-label">{{ __('Method') }}</label>
                <select id="method" name="method" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    @foreach (['POST', 'PUT', 'PATCH', 'DELETE', 'GET'] as $method)
                        <option @selected(($filters['method'] ?? '') === $method)>{{ $method }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-3 col-md-1">
                <label for="outcome" class="form-label">{{ __('Outcome') }}</label>
                <select id="outcome" name="outcome" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    <option value="success" @selected(($filters['outcome'] ?? '') === 'success')>{{ __('Success') }}</option>
                    <option value="error" @selected(($filters['outcome'] ?? '') === 'error')>{{ __('Error') }}</option>
                </select>
            </div>
            @include('core.security._date-filter')
        </div>
    </form>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('When') }}</th>
                        <th>{{ __('User') }}</th>
                        <th>{{ __('Company') }}</th>
                        <th>{{ __('Action') }}</th>
                        <th>{{ __('Page') }}</th>
                        <th class="text-end">{{ __('Result') }}</th>
                        <th class="text-end">{{ __('Time') }}</th>
                        <th>{{ __('IP address') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activities as $activity)
                        <tr>
                            <td class="text-nowrap">{{ $activity->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>{{ $activity->user?->name ?? '—' }}</td>
                            <td>{{ $activity->company?->code ?? '—' }}</td>
                            <td><span class="badge text-bg-light border">{{ $activity->method }}</span> <code>{{ $activity->route_name ?? '—' }}</code></td>
                            <td class="text-break"><small>{{ $activity->path }}</small></td>
                            <td class="text-end"><span class="badge text-bg-{{ $activity->status >= 500 ? 'danger' : ($activity->status >= 400 ? 'warning' : 'success') }}">{{ $activity->status }}</span></td>
                            <td class="text-end text-nowrap">{{ $activity->duration_ms }} ms</td>
                            <td>{{ $activity->ip_address }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-body-secondary py-4">{{ __('No activity recorded.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($activities->hasPages())
            <div class="card-footer">{{ $activities->links() }}</div>
        @endif
    </div>
@endsection
