@extends('layouts.erp')

@section('title', __('New Bank Receipt - BizNexus'))

@section('content_header')
    <h1>{{ __('New Bank Receipt') }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.bank-receipts.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="bank_account_id">{{ __('Bank Account') }}</label>
                            <select name="bank_account_id" class="form-control" required>
                                <option value="">{{ __('Select Bank Account') }}</option>
                                @foreach($bankAccounts as $account)
                                    <option value="{{ $account->id }}" @selected(old('bank_account_id') == $account->id)>
                                        {{ $account->bank_name }} — {{ $account->display_account_number }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="transaction_date">{{ __('Date') }}</label>
                            <input type="date" class="form-control" name="transaction_date" value="{{ old('transaction_date', now()->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="amount">{{ __('Amount') }}</label>
                            <input type="number" class="form-control" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="reference">{{ __('Reference') }}</label>
                            <input type="text" class="form-control" name="reference" value="{{ old('reference') }}">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description">{{ __('Description') }}</label>
                    <textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">{{ __('Save Bank Receipt') }}</button>
                    <a href="{{ route('finance.bank-receipts.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
