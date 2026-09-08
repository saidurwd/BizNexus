@extends('layouts.erp')

@section('title', 'Add Bank Account - BizNexus')

@section('content_header')
    <h1>Add Bank Account</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.bank-accounts.store') }}" method="POST">
                @csrf
                <input type="hidden" name="company_id" value="1">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="bank_name">Bank Name</label>
                            <input type="text" class="form-control" name="bank_name" value="{{ old('bank_name') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="branch_name">Branch Name</label>
                            <input type="text" class="form-control" name="branch_name" value="{{ old('branch_name') }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="account_name">Account Name</label>
                            <input type="text" class="form-control" name="account_name" value="{{ old('account_name') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="account_number">Account Number</label>
                            <input type="text" class="form-control" name="account_number" value="{{ old('account_number') }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="gl_account_id">GL Account</label>
                            <select name="gl_account_id" class="form-control" required>
                                <option value="">Select GL Account</option>
                                @foreach($glOptions as $opt)
                                    <option value="{{ $opt->id }}" @selected(old('gl_account_id') == $opt->id)>
                                        {{ $opt->account_code }} — {{ $opt->account_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="currency_id">Currency</label>
                            <select name="currency_id" class="form-control" required>
                                <option value="">Select Currency</option>
                                @foreach($currencyOptions as $opt)
                                    <option value="{{ $opt->id }}" @selected(old('currency_id') == $opt->id)>
                                        {{ $opt->code }} — {{ $opt->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="opening_balance">Opening Balance</label>
                            <input type="number" class="form-control" name="opening_balance" step="0.01" min="0" value="{{ old('opening_balance', 0) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" class="form-control">
                                <option value="active" @selected(old('status', 'active') == 'active')>Active</option>
                                <option value="inactive" @selected(old('status') == 'inactive')>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Save Bank Account</button>
                    <a href="{{ route('finance.bank-accounts.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
