@extends('layouts.erp')

@section('title', __('Security Events'))

@section('content_header')
    <h1>{{ __('Security Events') }}</h1>
@endsection

@section('content')
    <div class="row">
        @foreach ([
            ['failed_logins_24h', __('Failed sign-ins, last 24 hours'), 'warning', 'bi-person-x', 'login_failed'],
            ['lockouts_7d', __('Lockouts, last 7 days'), 'danger', 'bi-lock', 'lockout'],
            ['access_denied_24h', __('Access refused, last 24 hours'), 'secondary', 'bi-slash-circle', 'access_denied'],
            ['two_factor_disabled_30d', __('Two-factor turned off, last 30 days'), 'info', 'bi-shield-slash', 'two_factor_disabled'],
        ] as [$key, $label, $colour, $icon, $type])
            <div class="col-md-3 col-6">
                <a href="{{ route('core.security-events.index', ['type' => $type]) }}" class="text-decoration-none">
                    <div class="small-box text-bg-{{ $colour }}">
                        <div class="inner">
                            <h3>{{ $summary[$key] }}</h3>
                            <p>{{ $label }}</p>
                        </div>
                        <i class="small-box-icon bi {{ $icon }}" aria-hidden="true"></i>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <form method="GET" class="card mb-3">
        <div class="card-body row g-2 align-items-end">
            @include('core.security._user-filter')
            <div class="col-sm-6 col-md-2">
                <label for="type" class="form-label">{{ __('Event') }}</label>
                <select id="type" name="type" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    @foreach ($types as $type)
                        <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ __(ucfirst(str_replace('_', ' ', $type))) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-md-1">
                <label for="severity" class="form-label">{{ __('Severity') }}</label>
                <select id="severity" name="severity" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    @foreach (['info', 'warning', 'critical'] as $severity)
                        <option value="{{ $severity }}" @selected(($filters['severity'] ?? '') === $severity)>{{ __(ucfirst($severity)) }}</option>
                    @endforeach
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
                        <th>{{ __('Event') }}</th>
                        <th>{{ __('Severity') }}</th>
                        <th>{{ __('User or email') }}</th>
                        <th>{{ __('Details') }}</th>
                        <th>{{ __('IP address') }}</th>
                        <th>{{ __('Browser') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr>
                            <td class="text-nowrap">{{ $event->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>{{ __(ucfirst(str_replace('_', ' ', $event->type))) }}</td>
                            <td><span class="badge text-bg-{{ ['critical' => 'danger', 'warning' => 'warning', 'info' => 'info'][$event->severity] ?? 'secondary' }}">{{ __(ucfirst($event->severity)) }}</span></td>
                            <td>{{ $event->user?->name ?? $event->email ?? '—' }}</td>
                            <td><small>{{ collect($event->context ?? [])->map(fn ($value, $key) => str_replace('_', ' ', $key).': '.(is_scalar($value) ? str_replace('_', ' ', (string) $value) : json_encode($value)))->implode(' · ') }}</small></td>
                            <td>{{ $event->ip_address }}</td>
                            <td class="text-truncate" style="max-width: 220px" title="{{ $event->user_agent }}"><small>{{ $event->user_agent }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-body-secondary py-4">{{ __('No security events recorded.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($events->hasPages())
            <div class="card-footer">{{ $events->links() }}</div>
        @endif
    </div>
@endsection
