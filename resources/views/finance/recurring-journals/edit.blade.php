@extends('layouts.erp')

@section('title', __('Edit Recurring Journal'))

@section('content_header')
    <h1>{{ __('Edit Recurring Journal') }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.recurring-journals.update', $recurringJournal->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name">{{ __('Name') }}</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name', $recurringJournal->name) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="frequency">{{ __('Frequency') }}</label>
                            <select name="frequency" class="form-control" required>
                                <option value="DAILY" @selected(old('frequency', $recurringJournal->frequency) == 'DAILY')>{{ __('Daily') }}</option>
                                <option value="WEEKLY" @selected(old('frequency', $recurringJournal->frequency) == 'WEEKLY')>{{ __('Weekly') }}</option>
                                <option value="MONTHLY" @selected(old('frequency', $recurringJournal->frequency) == 'MONTHLY')>{{ __('Monthly') }}</option>
                                <option value="QUARTERLY" @selected(old('frequency', $recurringJournal->frequency) == 'QUARTERLY')>{{ __('Quarterly') }}</option>
                                <option value="YEARLY" @selected(old('frequency', $recurringJournal->frequency) == 'YEARLY')>{{ __('Yearly') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="next_run_date">{{ __('Next Run Date') }}</label>
                            <input type="date" class="form-control" name="next_run_date" value="{{ old('next_run_date', $recurringJournal->next_run_date->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="status">{{ __('Status') }}</label>
                            <select name="status" class="form-control">
                                <option value="active" @selected(old('status', $recurringJournal->status) == 'active')>{{ __('Active') }}</option>
                                <option value="paused" @selected(old('status', $recurringJournal->status) == 'paused')>{{ __('Paused') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description">{{ __('Description') }}</label>
                    <textarea class="form-control" name="description" rows="3">{{ old('description', $recurringJournal->description) }}</textarea>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">{{ __('Update Recurring Journal') }}</button>
                    <a href="{{ route('finance.recurring-journals.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
