@extends('layouts.erp')

@section('title', 'Create Account')

@section('content_header')
    <h1>Create Account</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.accounts.store') }}" method="POST">
                @csrf
                <input type="hidden" name="company_id" value="1">

                <div class="form-group">
                    <label for="account_code">Account Code</label>
                    <input type="text" class="form-control @error('account_code') is-invalid @enderror" 
                           id="account_code" name="account_code" value="{{ old('account_code') }}" required>
                    @error('account_code')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="account_name">Account Name</label>
                    <input type="text" class="form-control @error('account_name') is-invalid @enderror" 
                           id="account_name" name="account_name" value="{{ old('account_name') }}" required>
                    @error('account_name')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="account_type">Account Type</label>
                    <select class="form-control @error('account_type') is-invalid @enderror" 
                            id="account_type" name="account_type" required>
                        <option value="">Select Type</option>
                        <option value="ASSET" {{ old('account_type') === 'ASSET' ? 'selected' : '' }}>Asset</option>
                        <option value="LIABILITY" {{ old('account_type') === 'LIABILITY' ? 'selected' : '' }}>Liability</option>
                        <option value="EQUITY" {{ old('account_type') === 'EQUITY' ? 'selected' : '' }}>Equity</option>
                        <option value="REVENUE" {{ old('account_type') === 'REVENUE' ? 'selected' : '' }}>Revenue</option>
                        <option value="EXPENSE" {{ old('account_type') === 'EXPENSE' ? 'selected' : '' }}>Expense</option>
                    </select>
                    @error('account_type')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="normal_balance">Normal Balance</label>
                    <select class="form-control" id="normal_balance" name="normal_balance">
                        <option value="DEBIT">Debit</option>
                        <option value="CREDIT">Credit</option>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Create Account</button>
                    <a href="{{ route('finance.accounts.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
