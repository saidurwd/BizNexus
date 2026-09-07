@extends('adminlte::page')

@section('title', 'Create Payment')

@section('content_header')
    <h1>Create Payment</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.payments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="company_id" value="1">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="payment_date">Payment Date</label>
                            <input type="date" class="form-control" name="payment_date" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="payment_type">Payment Type</label>
                            <select class="form-control" name="payment_type" required>
                                <option value="CASH">Cash</option>
                                <option value="BANK_TRANSFER">Bank Transfer</option>
                                <option value="CHECK">Check</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="payment_account_id">Payment Account</label>
                            <select class="form-control" name="payment_account_id" required>
                                <option value="">Select Account</option>
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

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="payee_type">Payee Type</label>
                            <select class="form-control" name="payee_type" required>
                                <option value="SUPPLIER">Supplier</option>
                                <option value="CUSTOMER">Customer</option>
                                <option value="OTHER">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="payee_id">Payee</label>
                            <select class="form-control" name="payee_id">
                                <option value="">Select Payee</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="payee_name">Payee Name</label>
                    <input type="text" class="form-control" name="payee_name" value="{{ old('payee_name') }}" required>
                </div>

                <div class="form-group">
                    <label for="reference">Reference</label>
                    <input type="text" class="form-control" name="reference" value="{{ old('reference') }}">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Create Payment</button>
                    <a href="{{ route('finance.payments.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
