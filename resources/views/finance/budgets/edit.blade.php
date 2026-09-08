@extends('layouts.erp')

@section('title', 'Edit Budget')

@section('content_header')
    <h1>Edit Budget</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.budgets.update', $budget->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="name">Budget Name</label>
                    <input type="text" class="form-control" name="name" value="{{ old('name', $budget->name) }}" required>
                </div>

                <div class="form-group mt-3">
                    <label for="status">Status</label>
                    <select class="form-control" name="status" required>
                        <option value="DRAFT" {{ $budget->status === 'DRAFT' ? 'selected' : '' }}>Draft</option>
                        <option value="APPROVED" {{ $budget->status === 'APPROVED' ? 'selected' : '' }}>Approved</option>
                        <option value="ACTIVE" {{ $budget->status === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                        <option value="CLOSED" {{ $budget->status === 'CLOSED' ? 'selected' : '' }}>Closed</option>
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
