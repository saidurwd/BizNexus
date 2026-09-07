@extends('adminlte::page')

@section('title', 'Budget Details')

@section('content_header')
    <h1>Budget: {{ $budget->name }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Budget Information</h3>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <tr>
                    <th width="200">Name</th>
                    <td>{{ $budget->name }}</td>
                </tr>
                <tr>
                    <th>Fiscal Year</th>
                    <td>{{ $budget->fiscalYear?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="badge bg-{{ $budget->status === 'ACTIVE' ? 'success' : ($budget->status === 'APPROVED' ? 'info' : ($budget->status === 'DRAFT' ? 'secondary' : 'warning')) }}">
                            {{ $budget->status }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Created At</th>
                    <td>{{ $budget->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h3 class="card-title">Budget Lines</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th>Cost Center</th>
                        <th>Period</th>
                        <th class="text-right">Budget Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($budget->lines as $line)
                        <tr>
                            <td>{{ $line->account?->account_name ?? '-' }}</td>
                            <td>{{ $line->costCenter?->name ?? '-' }}</td>
                            <td>{{ $line->period }}</td>
                            <td class="text-right">{{ number_format($line->budget_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">No budget lines found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('finance.budgets.index') }}" class="btn btn-secondary">Back</a>
    </div>
@endsection
