@extends('layouts.erp')

@section('title', 'Create Supplier Debit Note')

@section('content_header')
    <h1>Create Supplier Debit Note</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.supplier-debit-notes.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="note_number">Debit Note Number</label>
                            <input type="text" class="form-control" name="note_number" value="{{ old('note_number') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="note_date">Debit Note Date</label>
                            <input type="date" class="form-control" name="note_date" value="{{ old('note_date', now()->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="supplier_id">Supplier</label>
                            <select class="form-control" name="supplier_id" required>
                                <option value="">Select Supplier</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>
                                        {{ $supplier->supplier_code }} — {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="amount">Amount</label>
                            <input type="number" class="form-control" name="amount" step="0.01" min="0" value="{{ old('amount') }}" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reference_type">Reference Type</label>
                    <input type="text" class="form-control" name="reference_type" value="{{ old('reference_type') }}" placeholder="e.g., supplier_invoice">
                </div>

                <div class="form-group">
                    <label for="reference_id">Reference ID</label>
                    <input type="number" class="form-control" name="reference_id" value="{{ old('reference_id') }}" placeholder="e.g., 123">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Create Debit Note</button>
                    <a href="{{ route('finance.supplier-debit-notes.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
