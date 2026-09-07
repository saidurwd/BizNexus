@extends('adminlte::page')

@section('title', 'Edit Tax Code')

@section('content_header')
    <h1>Edit Tax Code</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.taxes.update', $tax->id) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="company_id" value="{{ old('company_id', $tax->company_id) }}">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tax_code">Tax Code</label>
                            <input type="text" class="form-control" name="tax_code" value="{{ old('tax_code', $tax->tax_code) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tax_name">Tax Name</label>
                            <input type="text" class="form-control" name="tax_name" value="{{ old('tax_name', $tax->tax_name) }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="rate">Tax Rate (%)</label>
                            <input type="number" class="form-control" name="rate" step="0.01" min="0" max="100" value="{{ old('rate', $tax->rate) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tax_type">Tax Type</label>
                            <select class="form-control" name="tax_type" required>
                                <option value="VAT" @selected(old('tax_type', $tax->tax_type) == 'VAT')>VAT</option>
                                <option value="WITHHOLDING_TAX" @selected(old('tax_type', $tax->tax_type) == 'WITHHOLDING_TAX')>Withholding Tax</option>
                                <option value="INCOME_TAX" @selected(old('tax_type', $tax->tax_type) == 'INCOME_TAX')>Income Tax</option>
                                <option value="OTHER" @selected(old('tax_type', $tax->tax_type) == 'OTHER')>Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="is_inclusive">Tax Inclusive</label>
                            <select name="is_inclusive" class="form-control">
                                <option value="0" @selected(old('is_inclusive', $tax->is_inclusive) == '0')>No (Exclusive)</option>
                                <option value="1" @selected(old('is_inclusive', $tax->is_inclusive) == '1')>Yes (Inclusive)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" class="form-control">
                                <option value="active" @selected(old('status', $tax->status) == 'active')>Active</option>
                                <option value="inactive" @selected(old('status', $tax->status) == 'inactive')>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update Tax Code</button>
                    <a href="{{ route('finance.taxes.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
