@extends('layouts.erp')

@section('title', __('Workflow Details'))

@section('content_header')
    <h1>Workflow Instance #{{ $instance->id }}</h1>
    <div class="mt-2">
        <a href="{{ route('workflow.index') }}" class="btn btn-default">
            <i class="bi bi-arrow-left"></i> {{ __('Back to Dashboard') }}
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
                            <th>{{ __('Workflow') }}</th>
                            <td>{{ $instance->definition->name }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Entity Type') }}</th>
                            <td>{{ ucfirst(str_replace('_', ' ', $instance->entity_type)) }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Entity ID') }}</th>
                            <td>{{ $instance->entity_id }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Current State') }}</th>
                            <td>
                                <span class="badge bg-info">{{ $instance->current_state }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('Started At') }}</th>
                            <td>{{ $instance->started_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Completed At') }}</th>
                            <td>{{ $instance->completed_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Status') }}</th>
                            <td>
                                @if($instance->completed_at)
                                    <span class="badge bg-success">{{ __('Completed') }}</span>
                                @else
                                    <span class="badge bg-warning">{{ __('In Progress') }}</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Available Transitions') }}</h3>
                </div>
                <div class="card-body">
                    @php
                        $transitions = $instance->getAvailableTransitions();
                    @endphp
                    @if($transitions->isEmpty())
                        <p class="text-muted">{{ __('No further transitions available.') }}</p>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach($transitions as $transition)
                                <li class="mb-2">
                                    <span class="badge bg-primary">{{ $transition }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Approvals') }}</h3>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>{{ __('Approver') }}</th>
                                <th>{{ __('Role') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Comments') }}</th>
                                <th>{{ __('Acted At') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($instance->approvals as $approval)
                                <tr>
                                    <td>{{ $approval->approver_id ?? 'System' }}</td>
                                    <td>{{ $approval->role ?? '-' }}</td>
                                    <td>
                                        @php
                                            $badgeClass = 'secondary';
                                            if ($approval->status === 'approved') $badgeClass = 'success';
                                            elseif ($approval->status === 'rejected') $badgeClass = 'danger';
                                        @endphp
                                        <span class="badge bg-{{ $badgeClass }}">{{ ucfirst($approval->status) }}</span>
                                    </td>
                                    <td>{{ $approval->comments ?? '-' }}</td>
                                    <td>{{ $approval->acted_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">{{ __('No approvals found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Action History') }}</h3>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>{{ __('Action') }}</th>
                                <th>{{ __('From State') }}</th>
                                <th>{{ __('To State') }}</th>
                                <th>{{ __('User') }}</th>
                                <th>{{ __('Comments') }}</th>
                                <th>{{ __('Acted At') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($instance->actions as $action)
                                <tr>
                                    <td>{{ ucfirst($action->action) }}</td>
                                    <td>{{ $action->from_state ?? '-' }}</td>
                                    <td>{{ $action->to_state ?? '-' }}</td>
                                    <td>{{ $action->user?->name ?? 'System' }}</td>
                                    <td>{{ $action->comments ?? '-' }}</td>
                                    <td>{{ $action->acted_at->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">{{ __('No actions found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
