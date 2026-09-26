@extends('layouts.erp')

@section('title', __('Add Bank Account - BizNexus'))

@section('content_header')
    <h1>{{ __('Add Bank Account') }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.bank-accounts.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="bank_name">{{ __('Bank Name') }}</label>
                            <input type="text" class="form-control" name="bank_name" value="{{ old('bank_name') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="branch_name">{{ __('Branch Name') }}</label>
                            <input type="text" class="form-control" name="branch_name" value="{{ old('branch_name') }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="account_name">{{ __('Account Name') }}</label>
                            <input type="text" class="form-control" name="account_name" value="{{ old('account_name') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="account_number">{{ __('Account Number') }}</label>
                            <input type="text" class="form-control" name="account_number" value="{{ old('account_number') }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="gl_account_id">{{ __('GL Account') }}</label>
                            <select name="gl_account_id" class="form-control" required>
                                <option value="">{{ __('Select GL Account') }}</option>
                                @foreach($glOptions as $opt)
                                    <option value="{{ $opt->id }}" @selected(old('gl_account_id') == $opt->id)>
                                        {{ $opt->account_code }} — {{ $opt->account_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="currency_id">{{ __('Currency') }}</label>
                            <select name="currency_id" class="form-control" required>
                                <option value="">{{ __('Select Currency') }}</option>
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
                        <div class="mb-3">
                            <label for="opening_balance">{{ __('Opening Balance') }}</label>
                            <input type="number" class="form-control" name="opening_balance" step="0.01" min="0" value="{{ old('opening_balance', 0) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="status">{{ __('Status') }}</label>
                            <select name="status" class="form-control">
                                <option value="active" @selected(old('status', 'active') == 'active')>{{ __('Active') }}</option>
                                <option value="inactive" @selected(old('status') == 'inactive')>{{ __('Inactive') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">{{ __('Save Bank Account') }}</button>
                    <a href="{{ route('finance.bank-accounts.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
