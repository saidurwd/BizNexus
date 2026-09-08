@extends('layouts.erp')

@section('title', 'Accounting Periods')

@section('content_header')
    <h1>Accounting Periods</h1>
@endsection

@section('content')
    <div class="row">
        @foreach($fiscalYears as $fiscalYear)
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            {{ $fiscalYear->year_name }}
                            <span class="badge bg-{{ $fiscalYear->status === 'OPEN' ? 'success' : 'secondary' }}">
                                {{ $fiscalYear->status }}
                            </span>
                        </h3>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($fiscalYear->periods as $period)
                                    <tr>
                                        <td>{{ $period->period_name }}</td>
                                        <td>{{ $period->start_date->format('Y-m-d') }}</td>
                                        <td>{{ $period->end_date->format('Y-m-d') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $period->status === 'OPEN' ? 'success' : ($period->status === 'CLOSED' ? 'secondary' : 'warning') }}">
                                                {{ $period->status }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($period->status === 'OPEN')
                                                <form action="{{ route('core.periods.validate', $period->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-info">Validate</button>
                                                </form>
                                                <form action="{{ route('core.periods.close', $period->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Close this period?')">Close</button>
                                                </form>
                                            @endif
                                            @if($period->status === 'CLOSED')
                                                <form action="{{ route('core.periods.reopen', $period->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Reopen this period?')">Reopen</button>
                                                </form>
                                            @endif
                                            @if($period->status === 'OPEN')
                                                <form action="{{ route('core.periods.lock', $period->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Lock this period?')">Lock</button>
                                                </form>
                                            @endif
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
@endsection
