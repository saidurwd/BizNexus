@extends('layouts.erp')

@section('title', 'New Bank Payment - BizNexus')

@section('content_header')
    <h1>New Bank Payment</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.bank-payments.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="bank_account_id">Bank Account</label>
                            <select name="bank_account_id" class="form-control" required>
                                <option value="">Select Bank Account</option>
                                @foreach($bankAccounts as $account)
                                    <option value="{{ $account->id }}" @selected(old('bank_account_id') == $account->id)>
                                        {{ $account->bank_name }} — {{ $account->account_number }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="transaction_date">Date</label>
                            <input type="date" class="form-control" name="transaction_date" value="{{ old('transaction_date', now()->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="transaction_type">Type</label>
                            <select name="transaction_type" class="form-control" required>
                                <option value="WITHDRAWAL">Withdrawal</option>
                                <option value="TRANSFER">Transfer</option>
                                <option value="CHARGE">Bank Charge</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="amount">Amount</label>
                            <input type="number" class="form-control" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}" required>
                        </div>
                    </div>
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
                    <button type="submit" class="btn btn-primary">Save Bank Payment</button>
                    <a href="{{ route('finance.bank-payments.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
