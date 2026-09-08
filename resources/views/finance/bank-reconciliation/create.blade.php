@extends('layouts.erp')

@section('title', 'New Bank Reconciliation - BizNexus')

@section('content_header')
    <h1>New Bank Reconciliation</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.bank-reconciliation.store') }}" method="POST">
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
                            <label for="statement_date">Statement Date</label>
                            <input type="date" class="form-control" name="statement_date" value="{{ old('statement_date', now()->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="statement_balance">Statement Balance</label>
                            <input type="number" class="form-control" name="statement_balance" step="0.01" min="0" value="{{ old('statement_balance') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="book_balance">Book Balance</label>
                            <input type="number" class="form-control" name="book_balance" step="0.01" min="0" value="{{ old('book_balance') }}" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Save Reconciliation</button>
                    <a href="{{ route('finance.bank-reconciliation.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
