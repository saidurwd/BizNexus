@extends('layouts.erp')

@section('title', 'Accounting Periods')

@section('content_header')
    <h1>Accounting Periods</h1>
    <div class="mt-2">
        <a href="{{ route('core.fiscal-years.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create Fiscal Year
        </a>
    </div>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-hidden="true"></button>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-hidden="true"></button>
            {{ session('error') }}
        </div>
    @endif

    @if($fiscalYears->count() === 0)
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-calendar-x" style="font-size: 48px; color: #ccc;"></i>
                <h3 class="mt-3">No Fiscal Years Found</h3>
                <p class="text-muted">Create your first fiscal year to get started with accounting periods.</p>
                <a href="{{ route('core.fiscal-years.create') }}" class="btn btn-primary mt-2">
                    <i class="bi bi-plus-circle"></i> Create Fiscal Year
                </a>
            </div>
        </div>
    @else
        <div class="row">
            @foreach($fiscalYears as $fiscalYear)
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                {{ $fiscalYear->name }}
                                <span class="badge bg-{{ $fiscalYear->status === 'OPEN' ? 'success' : ($fiscalYear->status === 'CLOSED' ? 'secondary' : 'warning') }}">
                                    {{ $fiscalYear->status }}
                                </span>
                                @if($fiscalYear->is_current)
                                    <span class="badge bg-primary ms-1">Current</span>
                                @endif
                            </h3>
                            <div class="card-tools">
                                <small class="text-muted">
                                    {{ $fiscalYear->start_date->format('Y-m-d') }} to {{ $fiscalYear->end_date->format('Y-m-d') }}
                                </small>
                            </div>
                        </div>
                        <div class="card-body table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Period</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                        <th>Closed By</th>
                                        <th>Closed At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($fiscalYear->periods as $period)
                                        <tr>
                                            <td>{{ $period->period_name }}</td>
                                            <td>{{ $period->start_date->format('Y-m-d') }}</td>
                                            <td>{{ $period->end_date->format('Y-m-d') }}</td>
                                            <td>
                                                <span class="badge bg-{{ $period->status === 'OPEN' ? 'success' : ($period->status === 'CLOSED' ? 'secondary' : 'warning') }}">
                                                    {{ $period->status }}
                                                </span>
                                            </td>
                                            <td>{{ $period->closedBy?->name ?? '-' }}</td>
                                            <td>{{ $period->closed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                            <td>
                                                @if($period->status === 'OPEN')
                                                    <form action="{{ route('core.periods.validate', $period->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-info">Validate</button>
                                                    </form>
                                                    <form action="{{ route('core.periods.close', $period->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Close this period? This action will prevent posting in this period.')">Close</button>
                                                    </form>
                                                @endif
                                                @if($period->status === 'CLOSED')
                                                    <form action="{{ route('core.periods.reopen', $period->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Reopen this period? This will allow posting again.')">Reopen</button>
                                                    </form>
                                                @endif
                                                @if($period->status === 'OPEN')
                                                    <form action="{{ route('core.periods.lock', $period->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Lock this period? This is irreversible.')">Lock</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">No periods found for this fiscal year.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
