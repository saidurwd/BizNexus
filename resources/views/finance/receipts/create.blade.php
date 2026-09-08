@extends('layouts.erp')

@section('title', 'Create Receipt')

@section('content_header')
    <h1>Create Receipt</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.receipts.store') }}" method="POST">
                @csrf
                <input type="hidden" name="company_id" value="1">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="receipt_date">Receipt Date</label>
                            <input type="date" class="form-control" name="receipt_date" value="{{ old('receipt_date', now()->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="receipt_type">Receipt Type</label>
                            <select class="form-control" name="receipt_type" required>
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
                            <label for="receipt_account_id">Receipt Account</label>
                            <select class="form-control" name="receipt_account_id" required>
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
                            <label for="payer_type">Payer Type</label>
                            <select class="form-control" name="payer_type" required>
                                <option value="CUSTOMER">Customer</option>
                                <option value="SUPPLIER">Supplier</option>
                                <option value="OTHER">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="payer_id">Payer</label>
                            <select class="form-control" name="payer_id">
                                <option value="">Select Payer</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="payer_name">Payer Name</label>
                    <input type="text" class="form-control" name="payer_name" value="{{ old('payer_name') }}" required>
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
                    <button type="submit" class="btn btn-primary">Create Receipt</button>
                    <a href="{{ route('finance.receipts.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
