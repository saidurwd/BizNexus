@extends('layouts.erp')

@section('title', __('Audit Logs'))

@section('content_header')
    <h1>{{ __('Audit Logs') }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Filters') }}</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('core.audit.index') }}" class="form-inline">
                <div class="row">
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label>{{ __('Module') }}</label>
                            <select name="module" class="form-control">
                                <option value="">{{ __('All Modules') }}</option>
                                @foreach($modules as $module)
                                    <option value="{{ $module }}" {{ request('module') == $module ? 'selected' : '' }}>
                                        {{ ucfirst($module) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label>{{ __('Entity Type') }}</label>
                            <select name="entity_type" class="form-control">
                                <option value="">{{ __('All Types') }}</option>
                                @foreach($entityTypes as $type)
                                    <option value="{{ $type }}" {{ request('entity_type') == $type ? 'selected' : '' }}>
                                        {{ $type }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label>{{ __('Action') }}</label>
                            <select name="action" class="form-control">
                                <option value="">{{ __('All Actions') }}</option>
                                @foreach($actions as $action)
                                    <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                        {{ $action }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label>{{ __('User') }}</label>
                            <select name="user_id" class="form-control">
                                <option value="">{{ __('All Users') }}</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label>{{ __('Date From') }}</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label>{{ __('Date To') }}</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label>{{ __('Search') }}</label>
                            <input type="text" name="search" class="form-control" placeholder="{{ __('Search...') }}" value="{{ request('search') }}">
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel"></i> {{ __('Filter') }}
                        </button>
                        <a href="{{ route('core.audit.index') }}" class="btn btn-default">
                            <i class="bi bi-x-circle"></i> {{ __('Clear') }}
                        </a>
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
                        <th>{{ __('Date & Time') }}</th>
                        <th>{{ __('User') }}</th>
                        <th>{{ __('Module') }}</th>
                        <th>{{ __('Entity') }}</th>
                        <th>{{ __('Action') }}</th>
                        <th>{{ __('IP Address') }}</th>
                        <th class="text-center">{{ __('Details') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($auditLogs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>
                                {{ $log->user?->name ?? 'System' }}
                                @if($log->user?->email)
                                    <br><small class="text-muted">{{ $log->user->email }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-info">{{ ucfirst($log->module) }}</span>
                            </td>
                            <td>
                                <strong>{{ $log->entity_type }}</strong>
                                @if($log->entity_id)
                                    <br><small class="text-muted">ID: {{ $log->entity_id }}</small>
                                @endif
                            </td>
                            <td>
                                @php
                                    $badgeClass = 'secondary';
                                    if ($log->action === 'CREATE') $badgeClass = 'success';
                                    elseif ($log->action === 'UPDATE') $badgeClass = 'warning';
                                    elseif ($log->action === 'DELETE') $badgeClass = 'danger';
                                @endphp
                                <span class="badge bg-{{ $badgeClass }}">{{ $log->action }}</span>
                            </td>
                            <td><code>{{ $log->ip_address ?? '-' }}</code></td>
                            <td class="text-center">
                                <a href="{{ route('core.audit.show', $log->id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i> {{ __('View') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">{{ __('No audit logs found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $auditLogs->links() }}
        </div>
    </div>
@endsection
