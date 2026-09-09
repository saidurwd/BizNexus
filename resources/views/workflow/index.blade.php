@extends('layouts.erp')

@section('title', 'Workflow Dashboard')

@section('content_header')
    <h1>Workflow Dashboard</h1>
@endsection

@section('content')
    @if($pendingApprovals->isNotEmpty())
        <div class="row">
            @foreach($pendingApprovals as $entityType => $approvals)
                <div class="col-md-6 col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ ucfirst(str_replace('_', ' ', $entityType)) }}</h3>
                            <span class="badge bg-warning float-right">{{ count($approvals) }} Pending</span>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-bordered table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Current State</th>
                                        <th>Role</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($approvals as $approval)
                                        <tr>
                                            <td>
                                                <a href="{{ route('workflow.show', $approval['instance']['id']) }}">
                                                    #{{ $approval['instance']['entity_id'] }}
                                                </a>
                                            </td>
                                            <td>{{ $approval['instance']['current_state'] }}</td>
                                            <td>{{ $approval['role'] ?? 'N/A' }}</td>
                                            <td>
                                                <a href="{{ route('workflow.show', $approval['instance']['id']) }}" class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-check-circle text-success" style="font-size: 48px;"></i>
                <h3 class="mt-3">No Pending Approvals</h3>
                <p class="text-muted">All workflows are up to date.</p>
            </div>
        </div>
    @endif

    <div class="card mt-4">
        <div class="card-header">
            <h3 class="card-title">Recent Workflow Instances</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Entity Type</th>
                        <th>Entity ID</th>
                        <th>Current State</th>
                        <th>Started At</th>
                        <th>Completed At</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentInstances as $instance)
                        <tr>
                            <td>{{ $instance->id }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $instance->entity_type)) }}</td>
                            <td>{{ $instance->entity_id }}</td>
                            <td>{{ $instance->current_state }}</td>
                            <td>{{ $instance->started_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td>{{ $instance->completed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td>
                                @if($instance->completed_at)
                                    <span class="badge bg-success">Completed</span>
                                @else
                                    <span class="badge bg-warning">In Progress</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No workflow instances found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
