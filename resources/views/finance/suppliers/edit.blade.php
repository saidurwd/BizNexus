@extends('layouts.erp')

@section('title', 'Edit Supplier')

@section('content_header')
    <h1>Edit Supplier</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.suppliers.update', $supplier->id) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="company_id" value="{{ old('company_id', $supplier->company_id) }}">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="supplier_code">Supplier Code</label>
                            <input type="text" class="form-control @error('supplier_code') is-invalid @enderror" id="supplier_code" name="supplier_code" value="{{ old('supplier_code', $supplier->supplier_code) }}" required>
                            @error('supplier_code')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Supplier Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $supplier->name) }}" required>
                            @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="contact_person">Contact Person</label>
                            <input type="text" class="form-control" id="contact_person" name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $supplier->email) }}">
                            @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $supplier->phone) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tax_number">Tax Number</label>
                            <input type="text" class="form-control" id="tax_number" name="tax_number" value="{{ old('tax_number', $supplier->tax_number) }}">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3">{{ old('address', $supplier->address) }}</textarea>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="active" @selected(old('status', $supplier->status) === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $supplier->status) === 'inactive')>Inactive</option>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update Supplier</button>
                    <a href="{{ route('finance.suppliers.show', $supplier->id) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
