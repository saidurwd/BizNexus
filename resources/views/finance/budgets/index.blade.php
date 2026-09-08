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
                                <span class="badge bg-{{ $budget->status === 'ACTIVE' ? 'success' : ($budget->status === 'APPROVED' ? 'info' : ($budget->status === 'DRAFT' ? 'secondary' : 'warning')) }}">
                                    {{ $budget->status }}
                                </span>
                            </td>
                            <td>{{ $budget->created_at->format('Y-m-d') }}</td>
                            <td>
                                <a href="{{ route('finance.budgets.show', $budget->id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
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
