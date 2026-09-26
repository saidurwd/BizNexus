@extends('layouts.erp')

@section('title', __('Accounting Periods'))

@section('content_header')
    <h1>{{ __('Accounting Periods') }}</h1>
    <div class="mt-2">
        <a href="{{ route('core.fiscal-years.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> {{ __('Create Fiscal Year') }}
        </a>
    </div>
@endsection

@section('content')
    @if($fiscalYears->count() === 0)
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-calendar-x" style="font-size: 48px; color: #ccc;"></i>
                <h3 class="mt-3">{{ __('No Fiscal Years Found') }}</h3>
                <p class="text-muted">{{ __('Create your first fiscal year to get started with accounting periods.') }}</p>
                <a href="{{ route('core.fiscal-years.create') }}" class="btn btn-primary mt-2">
                    <i class="bi bi-plus-circle"></i> {{ __('Create Fiscal Year') }}
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
                                    <span class="badge bg-primary ms-1">{{ __('Current') }}</span>
                                @endif
                            </h3>
                            <div class="card-tools">
                                <small class="text-muted">
                                    {{ $fiscalYear->start_date->format('Y-m-d') }} to {{ $fiscalYear->end_date->format('Y-m-d') }}
                                </small>
                                @if ($fiscalYear->status === 'OPEN')
                                    @can('core.fiscal-years.close')
                                        <form action="{{ route('core.fiscal-years.close', $fiscalYear->id) }}" method="POST" class="d-inline ms-2" onsubmit="return confirm('Close {{ $fiscalYear->name }} and transfer profit or loss to retained earnings?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-dark">{{ __('Close year') }}</button>
                                        </form>
                                    @endcan
                                @elseif ($fiscalYear->status === 'CLOSED')
                                    @can('core.fiscal-years.reopen')
                                        <form action="{{ route('core.fiscal-years.reopen', $fiscalYear->id) }}" method="POST" class="d-inline ms-2" onsubmit="return confirm('Reopen {{ $fiscalYear->name }}? The closing entry will be reversed.')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning">{{ __('Reopen year') }}</button>
                                        </form>
                                    @endcan
                                @endif
                            </div>
                        </div>
                        <div class="card-body table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>{{ __('Period') }}</th>
                                        <th>{{ __('Start Date') }}</th>
                                        <th>{{ __('End Date') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Closed By') }}</th>
                                        <th>{{ __('Closed At') }}</th>
                                        <th>{{ __('Actions') }}</th>
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
                                                        <button type="submit" class="btn btn-sm btn-info">{{ __('Validate') }}</button>
                                                    </form>
                                                    <form action="{{ route('core.periods.close', $period->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm(@js(__('Close this period? This action will prevent posting in this period.')))">{{ __('Close') }}</button>
                                                    </form>
                                                @endif
                                                @if($period->status === 'CLOSED')
                                                    <form action="{{ route('core.periods.reopen', $period->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm(@js(__('Reopen this period? This will allow posting again.')))">{{ __('Reopen') }}</button>
                                                    </form>
                                                @endif
                                                @if($period->status === 'OPEN')
                                                    <form action="{{ route('core.periods.lock', $period->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm(@js(__('Lock this period? This is irreversible.')))">{{ __('Lock') }}</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">{{ __('No periods found for this fiscal year.') }}</td>
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
