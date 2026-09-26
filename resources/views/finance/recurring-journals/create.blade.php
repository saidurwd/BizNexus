@extends('layouts.erp')

@section('title', __('Add Recurring Journal'))

@section('content_header')
    <h1>{{ __('Add Recurring Journal') }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.recurring-journals.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name">{{ __('Name') }}</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="frequency">{{ __('Frequency') }}</label>
                            <select name="frequency" class="form-control" required>
                                <option value="DAILY">{{ __('Daily') }}</option>
                                <option value="WEEKLY">{{ __('Weekly') }}</option>
                                <option value="MONTHLY" selected>{{ __('Monthly') }}</option>
                                <option value="QUARTERLY">{{ __('Quarterly') }}</option>
                                <option value="YEARLY">{{ __('Yearly') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="next_run_date">{{ __('Next Run Date') }}</label>
                            <input type="date" class="form-control" name="next_run_date" value="{{ old('next_run_date', now()->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="status">{{ __('Status') }}</label>
                            <select name="status" class="form-control">
                                <option value="active" @selected(old('status', 'active') == 'active')>{{ __('Active') }}</option>
                                <option value="paused" @selected(old('status') == 'paused')>{{ __('Paused') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description">{{ __('Description') }}</label>
                    <textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">{{ __('Save Recurring Journal') }}</button>
                    <a href="{{ route('finance.recurring-journals.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
