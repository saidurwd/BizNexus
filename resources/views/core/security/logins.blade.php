@extends('layouts.erp')

@section('title', __('Login History'))

@section('content_header')
    <h1>{{ __('Login History') }}</h1>
@endsection

@section('content')
    <form method="GET" class="card mb-3">
        <div class="card-body row g-2 align-items-end">
            @include('core.security._user-filter')
            <div class="col-sm-6 col-md-2">
                <div class="form-check mt-4">
                    <input type="checkbox" id="active" name="active" value="1" class="form-check-input" @checked($filters['active'] ?? false)>
                    <label for="active" class="form-check-label">{{ __('Still signed in') }}</label>
                </div>
            </div>
            @include('core.security._date-filter')
        </div>
    </form>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('User') }}</th>
                        <th>{{ __('Signed in') }}</th>
                        <th>{{ __('Signed out') }}</th>
                        <th>{{ __('Method') }}</th>
                        <th>{{ __('IP address') }}</th>
                        <th>{{ __('Browser') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logins as $login)
                        <tr>
                            <td>{{ $login->user?->name ?? '—' }} <small class="text-body-secondary">{{ $login->user?->email }}</small></td>
                            <td class="text-nowrap">{{ $login->logged_in_at->format('Y-m-d H:i:s') }}</td>
                            <td class="text-nowrap">
                                @if ($login->logged_out_at)
                                    {{ $login->logged_out_at->format('Y-m-d H:i:s') }}
                                    <small class="text-body-secondary">({{ $login->logged_in_at->diffForHumans($login->logged_out_at, true) }})</small>
                                @else
                                    <span class="text-body-secondary">{{ __('No sign-out recorded') }}</span>
                                @endif
                            </td>
                            <td>{{ __(ucfirst(str_replace('_', ' ', $login->method))) }}</td>
                            <td>{{ $login->ip_address }}</td>
                            <td class="text-truncate" style="max-width: 280px" title="{{ $login->user_agent }}"><small>{{ $login->user_agent }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">{{ __('No sign-ins recorded.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logins->hasPages())
            <div class="card-footer">{{ $logins->links() }}</div>
        @endif
    </div>
@endsection
