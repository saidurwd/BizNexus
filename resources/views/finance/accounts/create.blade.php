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
                <input type="hidden" name="company_id" value="{{ session('active_company_id') ?? auth()->user()->companies->first()->id ?? 1 }}">

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="account_code">Account Code</label>
                            <input type="text" class="form-control @error('account_code') is-invalid @enderror" 
                                   id="account_code" name="account_code" value="{{ old('account_code') }}" required>
                            @error('account_code')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="account_name">Account Name</label>
                            <input type="text" class="form-control @error('account_name') is-invalid @enderror" 
                                   id="account_name" name="account_name" value="{{ old('account_name') }}" required>
                            @error('account_name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
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
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="account_category_id">Account Category</label>
                            <select class="form-control" id="account_category_id" name="account_category_id">
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('account_category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="normal_balance">Normal Balance</label>
                            <select class="form-control" id="normal_balance" name="normal_balance">
                                <option value="DEBIT" {{ old('normal_balance') === 'DEBIT' ? 'selected' : '' }}>Debit</option>
                                <option value="CREDIT" {{ old('normal_balance') === 'CREDIT' ? 'selected' : '' }}>Credit</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="parent_id">Parent Account</label>
                            <select class="form-control" id="parent_id" name="parent_id">
                                <option value="">None (Top Level)</option>
                                @foreach($parentAccounts as $parent)
                                    <option value="{{ $parent['id'] }}" {{ old('parent_id') == $parent['id'] ? 'selected' : '' }}>
                                        {!! $parent['indent'] !!}{{ $parent['account_code'] }} — {{ $parent['account_name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="is_group">Is Group</label>
                            <select class="form-control" id="is_group" name="is_group">
                                <option value="0" {{ old('is_group', 0) == 0 ? 'selected' : '' }}>No</option>
                                <option value="1" {{ old('is_group', 0) == 1 ? 'selected' : '' }}>Yes</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="is_postable">Is Postable</label>
                            <select class="form-control" id="is_postable" name="is_postable">
                                <option value="0" {{ old('is_postable', 1) == 0 ? 'selected' : '' }}>No</option>
                                <option value="1" {{ old('is_postable', 1) == 1 ? 'selected' : '' }}>Yes</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Create Account</button>
                    <a href="{{ route('finance.accounts.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
