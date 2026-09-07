@extends('adminlte::page')

@section('title', 'Add Tax Code')

@section('content_header')
    <h1>Add Tax Code</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.taxes.store') }}" method="POST">
                @csrf
                <input type="hidden" name="company_id" value="1">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tax_code">Tax Code</label>
                            <input type="text" class="form-control" name="tax_code" value="{{ old('tax_code') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tax_name">Tax Name</label>
                            <input type="text" class="form-control" name="tax_name" value="{{ old('tax_name') }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tax_rate">Tax Rate (%)</label>
                            <input type="number" class="form-control" name="tax_rate" step="0.01" min="0" max="100" value="{{ old('tax_rate') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tax_type">Tax Type</label>
                            <select class="form-control" name="tax_type" required>
                                <option value="SALES">Sales Tax</option>
                                <option value="PURCHASE">Purchase Tax</option>
                                <option value="VAT">VAT</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Save Tax Code</button>
                    <a href="{{ route('finance.taxes.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
