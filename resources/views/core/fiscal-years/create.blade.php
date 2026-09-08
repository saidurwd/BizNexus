@extends('layouts.erp')

@section('title', 'Create Fiscal Year')

@section('content_header')
    <h1>Create Fiscal Year</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('core.fiscal-years.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Fiscal Year Name</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}" placeholder="e.g., 2026-2027" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" class="form-control" name="start_date" value="{{ old('start_date') }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" class="form-control" name="end_date" value="{{ old('end_date') }}" required>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-3">
                    <button type="submit" class="btn btn-primary">Create Fiscal Year</button>
                    <a href="{{ route('core.periods.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
