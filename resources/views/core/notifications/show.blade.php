@extends('layouts.erp')

@section('title', __('Notification Details'))

@section('content_header')
    <h1>{{ __('Notification Details') }}</h1>
    <div class="mt-2">
        <a href="{{ route('core.notifications.index') }}" class="btn btn-default">
            <i class="bi bi-arrow-left"></i> {{ __('Back to Notifications') }}
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('General Information') }}</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>{{ __('Type') }}</th>
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
                            <th>{{ __('Status') }}</th>
                            <td>
                                @if($notification->read_at)
                                    <span class="badge bg-success">{{ __('Read') }}</span>
                                    <br><small class="text-muted">{{ $notification->read_at->format('Y-m-d H:i:s') }}</small>
                                @else
                                    <span class="badge bg-warning">{{ __('Unread') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('Created At') }}</th>
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
                    <h3 class="card-title">{{ __('Notification Details') }}</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>{{ __('Field') }}</th>
                                <th>{{ __('Value') }}</th>
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
