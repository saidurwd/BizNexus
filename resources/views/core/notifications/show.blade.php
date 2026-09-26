@extends('layouts.erp')

@section('title', 'Notification Details')

@section('content_header')
    <h1>Notification Details</h1>
    <div class="mt-2">
        <a href="{{ route('core.notifications.index') }}" class="btn btn-default">
            <i class="bi bi-arrow-left"></i> Back to Notifications
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">General Information</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Type</th>
                            <td>
                                @php
                                    $type = $notification->data['type'] ?? 'unknown';
                                    $badgeClass = 'secondary';
                                    $icon = 'bi bi-bell';
                                    
                                    if ($type === 'approval_requested') { $badgeClass = 'warning'; $icon = 'bi bi-inbox'; }
                                @endphp
                                <span class="badge bg-{{ $badgeClass }}">
                                    <i class="{{ $icon }}"></i> {{ ucfirst(str_replace('_', ' ', $type)) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @if($notification->read_at)
                                    <span class="badge bg-success">Read</span>
                                    <br><small class="text-muted">{{ $notification->read_at->format('Y-m-d H:i:s') }}</small>
                                @else
                                    <span class="badge bg-warning">Unread</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Created At</th>
                            <td>{{ $notification->created_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                        @if(! empty($notification->data['url']))
                            <tr>
                                <th>{{ __('Document') }}</th>
                                <td><a href="{{ $notification->data['url'] }}" class="btn btn-sm btn-primary"><i class="bi bi-box-arrow-up-right"></i> {{ __('Open :document :number', ['document' => $notification->data['document'] ?? '', 'number' => $notification->data['number'] ?? '']) }}</a></td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Notification Details</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($notification->data as $key => $value)
                                <tr>
                                    <td><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}</strong></td>
                                    <td>
                                        @if($key === 'amount' && is_numeric($value))
                                            {{ Formatter::amount($value) }}
                                        @elseif(is_bool($value))
                                            {{ $value ? 'Yes' : 'No' }}
                                        @else
                                            {{ $value }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
