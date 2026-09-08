@extends('layouts.erp')

@section('title', 'Add Cash Account - BizNexus')

@section('content_header')
    <h1>Add Cash Account</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.cash-accounts.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="code">Code</label>
                            <input type="text" class="form-control" name="code" value="{{ old('code') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
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
                            <label for="account_type">Account Type</label>
                            <select name="account_type" class="form-control">
                                <option value="CASH" @selected(old('account_type', 'CASH') == 'CASH')>Cash</option>
                                <option value="PETTY_CASH" @selected(old('account_type') == 'PETTY_CASH')>Petty Cash</option>
                                <option value="BANK" @selected(old('account_type') == 'BANK')>Bank</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="currency_code">Currency Code</label>
                            <input type="text" class="form-control" name="currency_code" value="{{ old('currency_code', 'BDT') }}" maxlength="3" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="opening_balance">Opening Balance</label>
                            <input type="number" class="form-control" name="opening_balance" step="0.01" min="0" value="{{ old('opening_balance', 0) }}" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" class="form-control">
                        <option value="active" @selected(old('status', 'active') == 'active')>Active</option>
                        <option value="inactive" @selected(old('status') == 'inactive')>Inactive</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea class="form-control" name="notes" rows="3">{{ old('notes') }}</textarea>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Save Cash Account</button>
                    <a href="{{ route('finance.cash-accounts.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
