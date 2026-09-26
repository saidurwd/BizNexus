@extends('layouts.erp')

@section('title', __('Create Fiscal Year'))

@section('content_header')
    <h1>{{ __('Create Fiscal Year') }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('core.fiscal-years.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name">{{ __('Fiscal Year Name') }}</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}" placeholder="e.g., 2026-2027" required>
                            @error('start_date')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="start_date">{{ __('Start Date') }}</label>
                            <input type="date" class="form-control" name="start_date" value="{{ old('start_date') }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="end_date">{{ __('End Date') }}</label>
                            <input type="date" class="form-control" name="end_date" value="{{ old('end_date') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="period_pattern">{{ __('Period calendar') }}</label>
                            <select class="form-control" name="period_pattern" id="period_pattern" required>
                                @foreach (\Modules\Core\Enums\FiscalCalendarPattern::cases() as $pattern)
                                    <option value="{{ $pattern->value }}" @selected(old('period_pattern', 'monthly') === $pattern->value)>{{ $pattern->label() }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">{{ __('An adjustment period on the last day of the year is added for audit adjustments and the year-end close.') }}</small>
                        </div>
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <button type="submit" class="btn btn-primary">{{ __('Create Fiscal Year') }}</button>
                    <a href="{{ route('core.periods.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
