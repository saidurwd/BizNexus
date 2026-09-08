@extends('layouts.erp')

@section('title', 'Edit Account')

@section('content_header')
    <h1>Edit Account</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('finance.accounts.update', $account->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="account_name">Account Name</label>
                    <input type="text" class="form-control @error('account_name') is-invalid @enderror" 
                           id="account_name" name="account_name" value="{{ old('account_name', $account->account_name) }}" required>
                    @error('account_name')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="active" {{ $account->status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $account->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update Account</button>
                    <a href="{{ route('finance.accounts.show', $account->id) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
