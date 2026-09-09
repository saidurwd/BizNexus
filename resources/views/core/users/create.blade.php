@extends('layouts.erp')

@section('title', 'Add User')

@section('content_header')
    <h1>Add User</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('core.users.store') }}" method="POST" enctype="multipart/form-data">
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
                    <label for="profile_picture">Profile Picture</label>
                    <input type="file" class="form-control" name="profile_picture" accept="image/*">
                    <small class="form-text text-muted">Max size: 2MB. Formats: jpeg, png, jpg, gif</small>
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

                <div class="form-group mt-3">
                    <label>Branches</label>
                    @foreach($branches as $companyId => $companyBranches)
                        @php $company = $companyBranches->first()->company @endphp
                        <strong>{{ $company->code }} — {{ $company->name }}</strong>
                        @foreach($companyBranches as $branch)
                            <div class="form-check ms-3">
                                <input type="checkbox" class="form-check-input" name="branches[]" value="{{ $branch->id }}" id="branch_{{ $branch->id }}" {{ in_array($branch->id, old('branches', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="branch_{{ $branch->id }}">
                                    {{ $branch->code }} — {{ $branch->name }}
                                </label>
                            </div>
                        @endforeach
                    @endforeach
                </div>

                <div class="form-group mt-3">
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