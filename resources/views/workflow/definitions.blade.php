@extends('layouts.erp')

@section('title', 'Workflow Definitions')

@section('content_header')
    <h1>Workflow Definitions</h1>
    <div class="mt-2">
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createDefinitionModal">
            <i class="bi bi-plus-circle"></i> Create Definition
        </button>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Entity Type</th>
                        <th>States</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($definitions as $definition)
                        <tr>
                            <td>{{ $definition->name }}</td>
                            <td>{{ $definition->entity_type }}</td>
                            <td>
                                @foreach($definition->states as $state => $transitions)
                                    <span class="badge bg-info me-1">{{ $state }}</span>
                                @endforeach
                            </td>
                            <td>
                                @if($definition->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editDefinitionModal{{ $definition->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No workflow definitions found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="createDefinitionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <form action="{{ route('workflow.definitions.store') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create Workflow Definition</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Entity Type</label>
                        <input type="text" class="form-control" name="entity_type" placeholder="e.g., journal, supplier_invoice" required>
                    </div>
                    <div class="form-group">
                        <label>States (JSON)</label>
                        <textarea class="form-control" name="states" rows="5" required placeholder='{"draft": ["submitted"], "submitted": ["approved", "rejected"]}'></textarea>
                    </div>
                    <div class="form-group">
                        <label>Transitions (JSON)</label>
                        <textarea class="form-control" name="transitions" rows="5" required placeholder='{"draft": ["submitted"], "submitted": ["approved", "rejected"]}'></textarea>
                    </div>
                    <div class="form-group">
                        <label>Approval Roles (JSON, optional)</label>
                        <textarea class="form-control" name="approval_roles" rows="3" placeholder='["MANAGER", "CFO"]'></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>

    @foreach($definitions as $definition)
        <div class="modal fade" id="editDefinitionModal{{ $definition->id }}" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <form action="{{ route('workflow.definitions.update', $definition->id) }}" method="POST" class="modal-content">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Workflow Definition</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" class="form-control" name="name" value="{{ $definition->name }}" required>
                        </div>
                        <div class="form-group">
                            <label>Entity Type</label>
                            <input type="text" class="form-control" name="entity_type" value="{{ $definition->entity_type }}" required>
                        </div>
                        <div class="form-group">
                            <label>States (JSON)</label>
                            <textarea class="form-control" name="states" rows="5" required>{{ json_encode($definition->states, JSON_PRETTY_PRINT) }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Transitions (JSON)</label>
                            <textarea class="form-control" name="transitions" rows="5" required>{{ json_encode($definition->transitions, JSON_PRETTY_PRINT) }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Approval Roles (JSON, optional)</label>
                            <textarea class="form-control" name="approval_roles" rows="3">{{ json_encode($definition->approval_roles, JSON_PRETTY_PRINT) }}</textarea>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" {{ $definition->is_active ? 'checked' : '' }}>
                            <label class="form-check-label">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
