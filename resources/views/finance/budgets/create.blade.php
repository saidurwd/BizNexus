@extends('layouts.erp')

@section('title', 'Create Budget')

@section('content_header')
    <h1>Create Budget</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.budgets.store') }}" method="POST" id="budget-form">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Budget Name</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="fiscal_year_id">Fiscal Year</label>
                            <select class="form-control" name="fiscal_year_id" required>
                                <option value="">Select Fiscal Year</option>
                                @foreach($fiscalYears as $fiscalYear)
                                    <option value="{{ $fiscalYear->id }}" {{ old('fiscal_year_id') == $fiscalYear->id ? 'selected' : '' }}>
                                        {{ $fiscalYear->name }} ({{ $fiscalYear->start_date->format('Y-m-d') }} to {{ $fiscalYear->end_date->format('Y-m-d') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-3">
                    <label for="status">Status</label>
                    <select class="form-control" name="status" required>
                        <option value="{{ \Modules\Finance\Models\Budget::STATUS_DRAFT }}" {{ old('status') == \Modules\Finance\Models\Budget::STATUS_DRAFT ? 'selected' : '' }}>Draft</option>
                        <option value="{{ \Modules\Finance\Models\Budget::STATUS_SUBMITTED }}" {{ old('status') == \Modules\Finance\Models\Budget::STATUS_SUBMITTED ? 'selected' : '' }}>Submitted</option>
                        <option value="{{ \Modules\Finance\Models\Budget::STATUS_APPROVED }}" {{ old('status') == \Modules\Finance\Models\Budget::STATUS_APPROVED ? 'selected' : '' }}>Approved</option>
                        <option value="{{ \Modules\Finance\Models\Budget::STATUS_REJECTED }}" {{ old('status') == \Modules\Finance\Models\Budget::STATUS_REJECTED ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>

                <div class="form-group mt-3">
                    <label for="description">Description</label>
                    <textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Create Budget</button>
                    <a href="{{ route('finance.budgets.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
