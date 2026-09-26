{{-- Content of the navbar bell's floating panel. Rendered into the AdminLTE navbar-notification dropdown. --}}
<span class="dropdown-item dropdown-header">{{ trans_choice(':count unread notification|:count unread notifications', $unread) }}</span>

@forelse ($notifications as $notification)
    <div class="dropdown-divider"></div>
    <a href="{{ route('core.notifications.open', $notification->id) }}" class="dropdown-item d-flex gap-2 align-items-start {{ $notification->read_at ? '' : 'fw-semibold' }}">
        <i class="bi {{ ($notification->data['type'] ?? '') === 'approval_requested' ? 'bi-inbox text-warning' : 'bi-bell text-secondary' }} mt-1" aria-hidden="true"></i>
        <span class="flex-grow-1 text-wrap small">{{ $notification->data['message'] ?? __('Notification') }}</span>
        <span class="text-body-secondary small text-nowrap">{{ $notification->created_at->diffForHumans(short: true) }}</span>
    </a>
@empty
    <div class="dropdown-divider"></div>
    <span class="dropdown-item text-body-secondary small">{{ __('No notifications yet.') }}</span>
@endforelse
