@extends('layouts.erp')

@section('title', 'Add Recurring Journal')

@section('content_header')
    <h1>Add Recurring Journal</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.recurring-journals.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="frequency">Frequency</label>
                            <select name="frequency" class="form-control" required>
                                <option value="DAILY">Daily</option>
                                <option value="WEEKLY">Weekly</option>
                                <option value="MONTHLY" selected>Monthly</option>
                                <option value="QUARTERLY">Quarterly</option>
                                <option value="YEARLY">Yearly</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="next_run_date">Next Run Date</label>
                            <input type="date" class="form-control" name="next_run_date" value="{{ old('next_run_date', now()->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" class="form-control">
                                <option value="active" @selected(old('status', 'active') == 'active')>Active</option>
                                <option value="paused" @selected(old('status') == 'paused')>Paused</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Save Recurring Journal</button>
                    <a href="{{ route('finance.recurring-journals.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
