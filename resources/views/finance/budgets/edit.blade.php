@extends('layouts.erp')

@section('title', 'Edit Budget')

@section('content_header')
    <h1>Edit Budget</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.budgets.update', $budget->id) }}" method="POST" id="budget-form">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="name">Budget Name</label>
                    <input type="text" class="form-control" name="name" value="{{ old('name', $budget->name) }}" required>
                </div>

                <div class="form-group mt-3">
                    <label for="description">Description</label>
                    <textarea class="form-control" name="description" rows="3">{{ old('description', $budget->description) }}</textarea>
                </div>

                <div class="form-group mt-3">
                    <label for="status">Status</label>
                    <select class="form-control" name="status" required>
                        <option value="{{ \Modules\Finance\Models\Budget::STATUS_DRAFT }}" {{ $budget->status === \Modules\Finance\Models\Budget::STATUS_DRAFT ? 'selected' : '' }}>Draft</option>
                        <option value="{{ \Modules\Finance\Models\Budget::STATUS_SUBMITTED }}" {{ $budget->status === \Modules\Finance\Models\Budget::STATUS_SUBMITTED ? 'selected' : '' }}>Submitted</option>
                        <option value="{{ \Modules\Finance\Models\Budget::STATUS_APPROVED }}" {{ $budget->status === \Modules\Finance\Models\Budget::STATUS_APPROVED ? 'selected' : '' }}>Approved</option>
                        <option value="{{ \Modules\Finance\Models\Budget::STATUS_REJECTED }}" {{ $budget->status === \Modules\Finance\Models\Budget::STATUS_REJECTED ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Update Budget</button>
                    <a href="{{ route('finance.budgets.show', $budget->id) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
