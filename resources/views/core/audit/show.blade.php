@extends('layouts.erp')

@section('title', __('Audit Log Details'))

@section('content_header')
    <h1>{{ __('Audit Log Details') }}</h1>
    <div class="mt-2">
        <a href="{{ route('core.audit.index') }}" class="btn btn-default">
            <i class="bi bi-arrow-left"></i> {{ __('Back to Audit Logs') }}
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
                            <th>{{ __('Date & Time') }}</th>
                            <td>{{ $auditLog->created_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('User') }}</th>
                            <td>
                                {{ $auditLog->user?->name ?? 'System' }}
                                @if($auditLog->user?->email)
                                    <br><small class="text-muted">{{ $auditLog->user->email }}</small>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('Module') }}</th>
                            <td>
                                <span class="badge bg-info">{{ ucfirst($auditLog->module) }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('Entity Type') }}</th>
                            <td>{{ $auditLog->entity_type }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Entity ID') }}</th>
                            <td>{{ $auditLog->entity_id ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Action') }}</th>
                            <td>
                                @php
                                    $badgeClass = 'secondary';
                                    if ($auditLog->action === 'CREATE') $badgeClass = 'success';
                                    elseif ($auditLog->action === 'UPDATE') $badgeClass = 'warning';
                                    elseif ($auditLog->action === 'DELETE') $badgeClass = 'danger';
                                @endphp
                                <span class="badge bg-{{ $badgeClass }}">{{ $auditLog->action }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('IP Address') }}</th>
                            <td><code>{{ $auditLog->ip_address ?? '-' }}</code></td>
                        </tr>
                        <tr>
                            <th>{{ __('User Agent') }}</th>
                            <td><small>{{ $auditLog->user_agent ?? '-' }}</small></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Changes') }}</h3>
                </div>
                <div class="card-body">
                    @if(empty($changes))
                        <p class="text-muted">{{ __('No detailed changes recorded for this action.') }}</p>
                    @else
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('Field') }}</th>
                                    <th>{{ __('Old Value') }}</th>
                                    <th>{{ __('New Value') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($changes as $change)
                                    <tr>
                                        <td><strong>{{ $change['field'] }}</strong></td>
                                        <td class="bg-danger bg-opacity-10">
                                            {!! $change['old'] ?? '<span class="text-muted">NULL</span>' !!}
                                        </td>
                                        <td class="bg-success bg-opacity-10">
                                            {!! $change['new'] ?? '<span class="text-muted">NULL</span>' !!}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
