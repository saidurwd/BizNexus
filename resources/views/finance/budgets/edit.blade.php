@extends('layouts.erp')

@section('title', __('Edit Budget'))

@section('content_header')
    <h1>{{ __('Edit Budget') }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.budgets.update', $budget->id) }}" method="POST" id="budget-form">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name">{{ __('Budget Name') }}</label>
                    <input type="text" class="form-control" name="name" value="{{ old('name', $budget->name) }}" required>
                </div>

                <div class="mb-3 mt-3">
                    <label for="description">{{ __('Description') }}</label>
                    <textarea class="form-control" name="description" rows="3">{{ old('description', $budget->description) }}</textarea>
                </div>

                <div class="mb-3 mt-3">
                    <label for="status">{{ __('Status') }}</label>
                    <select class="form-control" name="status" required>
                        <option value="{{ \Modules\Finance\Models\Budget::STATUS_DRAFT }}" {{ $budget->status === \Modules\Finance\Models\Budget::STATUS_DRAFT ? 'selected' : '' }}>{{ __('Draft') }}</option>
                        <option value="{{ \Modules\Finance\Models\Budget::STATUS_SUBMITTED }}" {{ $budget->status === \Modules\Finance\Models\Budget::STATUS_SUBMITTED ? 'selected' : '' }}>{{ __('Submitted') }}</option>
                        <option value="{{ \Modules\Finance\Models\Budget::STATUS_APPROVED }}" {{ $budget->status === \Modules\Finance\Models\Budget::STATUS_APPROVED ? 'selected' : '' }}>{{ __('Approved') }}</option>
                        <option value="{{ \Modules\Finance\Models\Budget::STATUS_REJECTED }}" {{ $budget->status === \Modules\Finance\Models\Budget::STATUS_REJECTED ? 'selected' : '' }}>{{ __('Rejected') }}</option>
                    </select>
                </div>

                <div class="mb-3 mt-4">
                    <button type="submit" class="btn btn-primary">{{ __('Update Budget') }}</button>
                    <a href="{{ route('finance.budgets.show', $budget->id) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
