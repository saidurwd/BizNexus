@extends('layouts.erp')

@section('title', 'Notifications')

@section('content_header')
    <h1>Notifications</h1>
    <div class="mt-2">
        @if(Auth::user()->unreadNotifications->count() > 0)
            <form action="{{ route('core.notifications.mark-all-read') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="bi bi-check2-all"></i> Mark All as Read
                </button>
            </form>
        @endif
        <form action="{{ route('core.notifications.destroy-all') }}" method="POST" class="d-inline" onsubmit="return confirm('Delete all notifications?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">
                <i class="bi bi-trash"></i> Clear All
            </button>
        </form>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filters</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('core.notifications.index') }}" class="form-inline">
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label>Type</label>
                            <select name="type" class="form-control">
                                <option value="">All Types</option>
                                @foreach($types as $type)
                                    <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>
                                        {{ \Illuminate\Support\Str::headline(class_basename($type)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label>Status</label>
                            <select name="read" class="form-control">
                                <option value="">All</option>
                                <option value="unread" {{ request('read') == 'unread' ? 'selected' : '' }}>Unread</option>
                                <option value="read" {{ request('read') == 'read' ? 'selected' : '' }}>Read</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-funnel"></i> Filter
                                </button>
                                <a href="{{ route('core.notifications.index') }}" class="btn btn-default">
                                    <i class="bi bi-x-circle"></i> Clear
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th width="50"></th>
                        <th>Notification</th>
                        <th>Type</th>
                        <th>Date & Time</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notifications as $notification)
                        @php
                            $data = $notification->data;
                            $isUnread = is_null($notification->read_at);
                            $type = $data['type'] ?? 'unknown';
                            $badgeClass = 'secondary';
                            $icon = 'bi bi-bell';
                            $label = ucfirst(str_replace('_', ' ', $type));
                            
                            if ($type === 'approval_requested') { $badgeClass = 'warning'; $icon = 'bi bi-inbox'; $label = __('Approval requested'); }
                            
                            $message = $data['message'] ?? 'Notification';
                            $rowClass = $isUnread ? 'table-active' : '';
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td class="text-center">
                                @if($isUnread)
                                    <i class="bi bi-circle-fill text-primary"></i>
                                @else
                                    <i class="bi bi-check2-circle text-muted"></i>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <div class="bg-{{ $badgeClass }} p-2 rounded-circle text-white">
                                            <i class="{{ $icon }}"></i>
                                        </div>
                                    </div>
                                    <div>
                                        @if(! empty($data['url']))
                                            <a href="{{ $data['url'] }}"><strong>{{ $message }}</strong></a>
                                        @else
                                            <strong>{{ $message }}</strong>
                                        @endif
                                        @if(isset($data['amount']))
                                            <br><small class="text-muted">{{ __('Amount') }}: {{ Formatter::amount($data['amount'], $data['currency'] ?? null) }} {{ $data['currency'] ?? '' }}@if(! empty($data['submitted_by'])) · {{ __('submitted by :name', ['name' => $data['submitted_by']]) }}@endif</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $badgeClass }}">{{ $label }}</span>
                            </td>
                            <td>{{ $notification->created_at->diffForHumans() }}</td>
                            <td class="text-center">
                                <a href="{{ route('core.notifications.show', $notification->id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <form action="{{ route('core.notifications.destroy', $notification->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this notification?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No notifications found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $notifications->links() }}
        </div>
    </div>
@endsection
