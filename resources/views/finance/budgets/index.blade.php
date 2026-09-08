@extends('layouts.erp')

@section('title', 'Budgets')

@section('content_header')
    <h1>Budgets</h1>
    <div class="mt-2">
        <a href="{{ route('finance.budgets.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create Budget
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('finance.budgets.index') }}" class="form-inline mb-3">
                <div class="form-group mr-2">
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="{{ \Modules\Finance\Models\Budget::STATUS_DRAFT }}" {{ request('status') == \Modules\Finance\Models\Budget::STATUS_DRAFT ? 'selected' : '' }}>Draft</option>
                        <option value="{{ \Modules\Finance\Models\Budget::STATUS_SUBMITTED }}" {{ request('status') == \Modules\Finance\Models\Budget::STATUS_SUBMITTED ? 'selected' : '' }}>Submitted</option>
                        <option value="{{ \Modules\Finance\Models\Budget::STATUS_APPROVED }}" {{ request('status') == \Modules\Finance\Models\Budget::STATUS_APPROVED ? 'selected' : '' }}>Approved</option>
                        <option value="{{ \Modules\Finance\Models\Budget::STATUS_REJECTED }}" {{ request('status') == \Modules\Finance\Models\Budget::STATUS_REJECTED ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-default">Filter</button>
            </form>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Fiscal Year</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($budgets as $budget)
                        <tr>
                            <td>{{ $budget->name }}</td>
                            <td>{{ $budget->fiscalYear?->name ?? '-' }}</td>
                            <td>
                                @php
                                    $badgeClass = 'secondary';
                                    if ($budget->status === 'APPROVED') $badgeClass = 'success';
                                    elseif ($budget->status === 'SUBMITTED') $badgeClass = 'info';
                                    elseif ($budget->status === 'REJECTED') $badgeClass = 'danger';
                                    elseif ($budget->status === 'ACTIVE') $badgeClass = 'primary';
                                @endphp
                                <span class="badge bg-{{ $badgeClass }}">
                                    {{ $budget->status }}
                                </span>
                            </td>
                            <td>{{ $budget->created_at->format('Y-m-d') }}</td>
                            <td>
                                <a href="{{ route('finance.budgets.show', $budget->id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($budget->isDraft())
                                    <a href="{{ route('finance.budgets.edit', $budget->id) }}" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('finance.budgets.destroy', $budget->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No budgets found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $budgets->links() }}
        </div>
    </div>
@endsection
