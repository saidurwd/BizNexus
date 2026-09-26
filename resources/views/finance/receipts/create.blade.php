@extends('layouts.erp')

@section('title', __('Create Receipt'))

@section('content_header')
    <h1>{{ __('Create Receipt') }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.receipts.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="receipt_date">{{ __('Receipt Date') }}</label>
                            <input type="date" class="form-control" name="receipt_date" value="{{ old('receipt_date', now()->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="receipt_type">{{ __('Receipt Type') }}</label>
                            <select class="form-control" name="receipt_type" required>
                                <option value="CASH">{{ __('Cash') }}</option>
                                <option value="BANK_TRANSFER">{{ __('Bank Transfer') }}</option>
                                <option value="CHECK">{{ __('Check') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="receipt_account_id">{{ __('Receipt Account') }}</label>
                            <select class="form-control" name="receipt_account_id" required>
                                <option value="">{{ __('Select Account') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="amount">{{ __('Amount') }}</label>
                            <input type="number" class="form-control" name="amount" step="0.01" min="0" value="{{ old('amount') }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="payer_type">{{ __('Payer Type') }}</label>
                            <select class="form-control" name="payer_type" required>
                                <option value="CUSTOMER">{{ __('Customer') }}</option>
                                <option value="SUPPLIER">{{ __('Supplier') }}</option>
                                <option value="OTHER">{{ __('Other') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="payer_id">{{ __('Payer') }}</label>
                            <select class="form-control" name="payer_id">
                                <option value="">{{ __('Select Payer') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="payer_name">{{ __('Payer Name') }}</label>
                    <input type="text" class="form-control" name="payer_name" value="{{ old('payer_name') }}" required>
                </div>

                <div class="mb-3">
                    <label for="reference">{{ __('Reference') }}</label>
                    <input type="text" class="form-control" name="reference" value="{{ old('reference') }}">
                </div>

                <div class="mb-3">
                    <label for="description">{{ __('Description') }}</label>
                    <textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">{{ __('Create Receipt') }}</button>
                    <a href="{{ route('finance.receipts.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
