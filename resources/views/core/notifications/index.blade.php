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
                        <div class="form-group">
                            <label>Type</label>
                            <select name="type" class="form-control">
                                <option value="">All Types</option>
                                @foreach($types as $type)
                                    <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>
                                        @php
                                            $labels = [
                                                'budget_exceeded' => 'Budget Exceeded',
                                                'invoice_approval' => 'Invoice Approval',
                                                'journal_approval' => 'Journal Approval',
                                                'payment_approved' => 'Payment Approved',
                                                'period_closing' => 'Period Closing',
                                            ];
                                            echo $labels[$type] ?? ucfirst(str_replace('_', ' ', $type));
                                        @endphp
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="read" class="form-control">
                                <option value="">All</option>
                                <option value="unread" {{ request('read') == 'unread' ? 'selected' : '' }}>Unread</option>
                                <option value="read" {{ request('read') == 'read' ? 'selected' : '' }}>Read</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
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
                            
                            if ($type === 'budget_exceeded') { $badgeClass = 'danger'; $icon = 'bi bi-calculator'; $label = 'Budget Exceeded'; }
                            elseif ($type === 'invoice_approval') { $badgeClass = 'warning'; $icon = 'bi bi-file-earmark-text'; $label = 'Invoice Approval'; }
                            elseif ($type === 'journal_approval') { $badgeClass = 'info'; $icon = 'bi bi-journal-text'; $label = 'Journal Approval'; }
                            elseif ($type === 'payment_approved') { $badgeClass = 'success'; $icon = 'bi bi-check-circle'; $label = 'Payment Approved'; }
                            elseif ($type === 'period_closing') { $badgeClass = 'primary'; $icon = 'bi bi-calendar-range'; $label = 'Period Closing'; }
                            
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
                                    <div class="mr-3">
                                        <div class="bg-{{ $badgeClass }} p-2 rounded-circle text-white">
                                            <i class="{{ $icon }}"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <strong>{{ $message }}</strong>
                                        @if(isset($data['amount']))
                                            <br><small class="text-muted">Amount: {{ number_format($data['amount'], 2) }}</small>
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
