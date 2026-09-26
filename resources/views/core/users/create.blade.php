@extends('layouts.erp')

@section('title', __('Add User'))

@section('content_header')
    <h1>{{ __('Add New User') }}</h1>
@endsection

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">{{ __('User Information') }}</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('core.users.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name">{{ __('Full Name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{ old('name') }}" placeholder="{{ __('Enter full name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="email">{{ __('Email Address') }} <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" id="email" value="{{ old('email') }}" placeholder="{{ __('Enter email address') }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="password">{{ __('Password') }} <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" id="password" placeholder="{{ __('Enter password') }}" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">{{ __('At least 12 characters, with upper and lower case letters, a number and a symbol.') }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="password_confirmation">{{ __('Confirm Password') }} <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password_confirmation" id="password_confirmation" placeholder="{{ __('Confirm password') }}" required>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="profile_picture">{{ __('Profile Picture') }}</label>
                    <div class="input-group">
                        <input type="file" class="form-control" name="profile_picture" accept="image/*" id="profile_picture">
                    </div>
                    <small class="form-text text-muted">{{ __('Max size: 2MB. Formats: jpeg, png, jpg, gif') }}</small>
                </div>

                <hr class="my-4">

                <div class="mb-3">
                    <label class="fw-bold">{{ __('Companies') }}</label>
                    @error('companies')<div class="text-danger small mb-1">{{ $message }}</div>@enderror
                    <div class="row">
                        @foreach($companies as $company)
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="companies[]" value="{{ $company->id }}" id="company_{{ $company->id }}" {{ in_array($company->id, old('companies', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="company_{{ $company->id }}">
                                        <strong>{{ $company->code }}</strong> — {{ $company->name }}
                                    </label>
                                </div>
                                <div class="form-check ms-4">
                                    <input type="checkbox" class="form-check-input" name="all_branches[]" value="{{ $company->id }}" id="all_branches_{{ $company->id }}" {{ in_array($company->id, old('all_branches', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label small text-muted" for="all_branches_{{ $company->id }}">{{ __('All branches, including future ones') }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mb-3">
                    <label class="fw-bold">{{ __('Branches') }}</label>
                    @foreach($branches as $companyId => $companyBranches)
                        @php $company = $companyBranches->first()->company @endphp
                        <div class="card card-outline card-secondary mb-2">
                            <div class="card-body py-2">
                                <h6 class="card-title mb-2 text-muted">
                                    <i class="bi bi-building me-1"></i>
                                    {{ $company->code }} — {{ $company->name }}
                                </h6>
                                <div class="row">
                                    @foreach($companyBranches as $branch)
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="branches[]" value="{{ $branch->id }}" id="branch_{{ $branch->id }}" {{ in_array($branch->id, old('branches', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="branch_{{ $branch->id }}">
                                                    <strong>{{ $branch->code }}</strong> — {{ $branch->name }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mb-3">
                    <label class="fw-bold">{{ __('Roles') }}</label>
                    @error('roles')<div class="text-danger small mb-1">{{ $message }}</div>@enderror
                    <div class="row">
                        @foreach($roles as $role)
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="roles[]" value="{{ $role->id }}" id="role_{{ $role->id }}" {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="role_{{ $role->id }}">
                                        {{ $role->name }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mb-3 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> {{ __('Save User') }}
                    </button>
                    <a href="{{ route('core.users.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-lg me-1"></i> {{ __('Cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
