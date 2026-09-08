@extends('layouts.erp')

@section('title', 'Add User')

@section('content_header')
    <h1>Add User</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('core.users.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" name="email" value="{{ old('email') }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="password_confirmation">Confirm Password</label>
                            <input type="password" class="form-control" name="password_confirmation" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Companies</label>
                    @foreach($companies as $company)
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="companies[]" value="{{ $company->id }}" id="company_{{ $company->id }}" {{ in_array($company->id, old('companies', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="company_{{ $company->id }}">
                                {{ $company->code }} — {{ $company->name }}
                            </label>
                        </div>
                    @endforeach
                </div>

                <div class="form-group">
                    <label>Roles</label>
                    @foreach($roles as $role)
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="roles[]" value="{{ $role->id }}" id="role_{{ $role->id }}" {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="role_{{ $role->id }}">
                                {{ $role->name }}
                            </label>
                        </div>
                    @endforeach
                </div>

                <div class="form-group mt-3">
                    <button type="submit" class="btn btn-primary">Save User</button>
                    <a href="{{ route('core.users.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
