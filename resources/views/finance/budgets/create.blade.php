@extends('layouts.erp')

@section('title', __('Create Budget'))

@section('content_header')
    <h1>{{ __('Create Budget') }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.budgets.store') }}" method="POST" id="budget-form">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name">{{ __('Budget Name') }}</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="fiscal_year_id">{{ __('Fiscal Year') }}</label>
                            <select class="form-control" name="fiscal_year_id" required>
                                <option value="">{{ __('Select Fiscal Year') }}</option>
                                @foreach($fiscalYears as $fiscalYear)
                                    <option value="{{ $fiscalYear->id }}" {{ old('fiscal_year_id') == $fiscalYear->id ? 'selected' : '' }}>
                                        {{ $fiscalYear->name }} ({{ $fiscalYear->start_date->format('Y-m-d') }} to {{ $fiscalYear->end_date->format('Y-m-d') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <label for="status">{{ __('Status') }}</label>
                    <select class="form-control" name="status" required>
                        <option value="{{ \Modules\Finance\Models\Budget::STATUS_DRAFT }}" {{ old('status') == \Modules\Finance\Models\Budget::STATUS_DRAFT ? 'selected' : '' }}>{{ __('Draft') }}</option>
                        <option value="{{ \Modules\Finance\Models\Budget::STATUS_SUBMITTED }}" {{ old('status') == \Modules\Finance\Models\Budget::STATUS_SUBMITTED ? 'selected' : '' }}>{{ __('Submitted') }}</option>
                        <option value="{{ \Modules\Finance\Models\Budget::STATUS_APPROVED }}" {{ old('status') == \Modules\Finance\Models\Budget::STATUS_APPROVED ? 'selected' : '' }}>{{ __('Approved') }}</option>
                        <option value="{{ \Modules\Finance\Models\Budget::STATUS_REJECTED }}" {{ old('status') == \Modules\Finance\Models\Budget::STATUS_REJECTED ? 'selected' : '' }}>{{ __('Rejected') }}</option>
                    </select>
                </div>

                <div class="mb-3 mt-3">
                    <label for="description">{{ __('Description') }}</label>
                    <textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
                </div>

                <div class="mb-3 mt-4">
                    <button type="submit" class="btn btn-primary">{{ __('Create Budget') }}</button>
                    <a href="{{ route('finance.budgets.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
