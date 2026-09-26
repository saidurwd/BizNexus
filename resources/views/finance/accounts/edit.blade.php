@extends('layouts.erp')

@section('title', __('Edit Account'))

@section('content_header')
    <h1>{{ __('Edit Account') }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.accounts.update', $account->id) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="company_id" value="{{ $account->company_id }}">

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="account_code">{{ __('Account Code') }}</label>
                            <input type="text" class="form-control @error('account_code') is-invalid @enderror" 
                                   id="account_code" name="account_code" value="{{ old('account_code', $account->account_code) }}" required>
                            @error('account_code')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="account_name">{{ __('Account Name') }}</label>
                            <input type="text" class="form-control @error('account_name') is-invalid @enderror" 
                                   id="account_name" name="account_name" value="{{ old('account_name', $account->account_name) }}" required>
                            @error('account_name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="account_type">{{ __('Account Type') }}</label>
                            <select class="form-control @error('account_type') is-invalid @enderror" 
                                    id="account_type" name="account_type" required>
                                <option value="">{{ __('Select Type') }}</option>
                                <option value="ASSET" {{ old('account_type', $account->account_type) === 'ASSET' ? 'selected' : '' }}>{{ __('Asset') }}</option>
                                <option value="LIABILITY" {{ old('account_type', $account->account_type) === 'LIABILITY' ? 'selected' : '' }}>{{ __('Liability') }}</option>
                                <option value="EQUITY" {{ old('account_type', $account->account_type) === 'EQUITY' ? 'selected' : '' }}>{{ __('Equity') }}</option>
                                <option value="REVENUE" {{ old('account_type', $account->account_type) === 'REVENUE' ? 'selected' : '' }}>{{ __('Revenue') }}</option>
                                <option value="EXPENSE" {{ old('account_type', $account->account_type) === 'EXPENSE' ? 'selected' : '' }}>{{ __('Expense') }}</option>
                            </select>
                            @error('account_type')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="account_category_id">{{ __('Account Category') }}</label>
                            <select class="form-control" id="account_category_id" name="account_category_id">
                                <option value="">{{ __('Select Category') }}</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('account_category_id', $account->account_category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="level">{{ __('Level') }}</label>
                            <input type="number" class="form-control" id="level" name="level" value="{{ old('level', $account->level ?? 1) }}" min="1">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="normal_balance">{{ __('Normal Balance') }}</label>
                            <select class="form-control" id="normal_balance" name="normal_balance">
                                <option value="DEBIT" {{ old('normal_balance', $account->normal_balance) === 'DEBIT' ? 'selected' : '' }}>{{ __('Debit') }}</option>
                                <option value="CREDIT" {{ old('normal_balance', $account->normal_balance) === 'CREDIT' ? 'selected' : '' }}>{{ __('Credit') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="currency_id">{{ __('Currency') }}</label>
                            <select class="form-control" id="currency_id" name="currency_id">
                                <option value="">{{ __('Default Currency') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="status">{{ __('Status') }}</label>
                            <select class="form-control" id="status" name="status">
                                <option value="active" {{ old('status', $account->status) === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                <option value="inactive" {{ old('status', $account->status) === 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="parent_id">{{ __('Parent Account') }}</label>
                            <select class="form-control" id="parent_id" name="parent_id">
                                <option value="">{{ __('None (Top Level)') }}</option>
                                @foreach($parentAccounts as $parent)
                                    <option value="{{ $parent['id'] }}" {{ $account->parent_id == $parent['id'] ? 'selected' : '' }}>
                                        {!! $parent['indent'] !!}{{ $parent['account_code'] }} — {{ $parent['account_name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="is_group">{{ __('Is Group') }}</label>
                            <select class="form-control" id="is_group" name="is_group">
                                <option value="0" {{ old('is_group', $account->is_group) == 0 ? 'selected' : '' }}>{{ __('No') }}</option>
                                <option value="1" {{ old('is_group', $account->is_group) == 1 ? 'selected' : '' }}>{{ __('Yes') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="is_postable">{{ __('Is Postable') }}</label>
                            <select class="form-control" id="is_postable" name="is_postable">
                                <option value="0" {{ old('is_postable', $account->is_postable) == 0 ? 'selected' : '' }}>{{ __('No') }}</option>
                                <option value="1" {{ old('is_postable', $account->is_postable) == 1 ? 'selected' : '' }}>{{ __('Yes') }}</option>
                            </select>
                        </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="revalue_foreign_currency">{{ __('Revalue foreign currency') }}</label>
                            <select class="form-control" id="revalue_foreign_currency" name="revalue_foreign_currency">
                                <option value="0" @selected(old('revalue_foreign_currency', (int) $account->revalue_foreign_currency) == 0)>{{ __('No') }}</option>
                                <option value="1" @selected(old('revalue_foreign_currency', (int) $account->revalue_foreign_currency) == 1)>{{ __('Yes (monetary item)') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="is_control_account">{{ __('Control account') }}</label>
                            <select class="form-control" id="is_control_account" name="is_control_account">
                                <option value="0" @selected(old('is_control_account', (int) $account->is_control_account) == 0)>{{ __('No') }}</option>
                                <option value="1" @selected(old('is_control_account', (int) $account->is_control_account) == 1)>{{ __('Yes (sub-ledger postings only)') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="cash_flow_category">{{ __('Cash flow category') }}</label>
                            <select class="form-control" id="cash_flow_category" name="cash_flow_category">
                                <option value="">—</option>
                                @foreach (\Modules\Finance\Enums\CashFlowCategory::cases() as $category)
                                    <option value="{{ $category->value }}" @selected(old('cash_flow_category', $account->cash_flow_category?->value) === $category->value)>{{ $category->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="is_current">{{ __('Balance sheet class') }}</label>
                            <select class="form-control" id="is_current" name="is_current">
                                <option value="">—</option>
                                <option value="1" @selected(old('is_current', $account->is_current) === '1' || old('is_current', $account->is_current) === true)>{{ __('Current') }}</option>
                                <option value="0" @selected(old('is_current', $account->is_current) === '0' || old('is_current', $account->is_current) === false)>{{ __('Non-current') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description">{{ __('Description') }}</label>
                    <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $account->description) }}</textarea>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">{{ __('Update Account') }}</button>
                    <a href="{{ route('finance.accounts.show', $account->id) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
