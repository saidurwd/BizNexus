@extends('layouts.erp')

@section('title', __('New Bank Reconciliation - BizNexus'))

@section('content_header')
    <h1>{{ __('New Bank Reconciliation') }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.bank-reconciliation.store') }}" method="POST">
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
                            <label for="statement_date">{{ __('Statement Date') }}</label>
                            <input type="date" class="form-control" name="statement_date" value="{{ old('statement_date', now()->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="statement_balance">{{ __('Statement Balance') }}</label>
                            <input type="number" class="form-control" name="statement_balance" step="0.01" min="0" value="{{ old('statement_balance') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="book_balance">{{ __('Book Balance') }}</label>
                            <input type="number" class="form-control" name="book_balance" step="0.01" min="0" value="{{ old('book_balance') }}" required>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">{{ __('Save Reconciliation') }}</button>
                    <a href="{{ route('finance.bank-reconciliation.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
