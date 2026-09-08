@extends('layouts.erp')

@section('title', 'Add Customer')

@section('content_header')
    <h1>Add Customer</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.customers.store') }}" method="POST">
                @csrf
                <input type="hidden" name="company_id" value="1">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="customer_code">Customer Code</label>
                            <input type="text" class="form-control" name="customer_code" value="{{ old('customer_code') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Customer Name</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="contact_person">Contact Person</label>
                            <input type="text" class="form-control" name="contact_person" value="{{ old('contact_person') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" name="email" value="{{ old('email') }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" class="form-control" name="phone" value="{{ old('phone') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tax_number">Tax Number</label>
                            <input type="text" class="form-control" name="tax_number" value="{{ old('tax_number') }}">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea class="form-control" name="address" rows="3">{{ old('address') }}</textarea>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Save Customer</button>
                    <a href="{{ route('finance.customers.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
